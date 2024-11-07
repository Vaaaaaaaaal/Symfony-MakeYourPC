<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Entity\Product;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ProductType;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Review;

class ProductController extends AbstractController
{
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    #[Route('/products', name: 'app_products')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $priceMin = $request->query->get('price_min') ? (float) $request->query->get('price_min') : null;
        $priceMax = $request->query->get('price_max') ? (float) $request->query->get('price_max') : null;
        $type = $request->query->get('type');
        $rating = $request->query->get('rating') ? (float) $request->query->get('rating') : null;

        $products = $productRepository->findByFilters($search, $priceMin, $priceMax, $type, $rating);

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    private function filterProducts(array $products, Request $request): array
    {
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $type = $request->query->get('type');
        $rating = $request->query->get('rating');

        return array_filter($products, function($product) use ($priceMin, $priceMax, $type, $rating) {
            if ($priceMin !== null && $product['price'] < $priceMin) {
                return false;
            }
            if ($priceMax !== null && $product['price'] > $priceMax) {
                return false;
            }
            if ($type !== null && $type !== '' && $product['type'] !== $type) {
                return false;
            }
            if ($rating !== null && $rating !== '' && $product['rating'] < $rating) {
                return false;
            }
            return true;
        });
    }

    #[Route('/admin/products', name: 'app_admin_products')]
    public function manageProducts(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        
        // Ajout de logs pour le débogage
        foreach ($products as $product) {
            dump([
                'name' => $product->getName(),
                'imagePath' => $product->getImagePath(),
                'full_path' => 'public/images/products/' . $product->getImagePath()
            ]);
        }
        
        return $this->render('admin/manage_products.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/admin/product/add', name: 'app_add_product', methods: ['GET', 'POST'])]
    public function addProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Débogage des données du formulaire
                dump('Données du formulaire:', $form->getData());
                
                // Gestion de l'image
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    dump('Image reçue:', [
                        'nom' => $imageFile->getClientOriginalName(),
                        'taille' => $imageFile->getSize(),
                        'type' => $imageFile->getMimeType()
                    ]);

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    dump('Nouveau nom de fichier:', $newFilename);
                    dump('Chemin de destination:', $this->getParameter('products_directory'));

                    try {
                        $imageFile->move(
                            $this->getParameter('products_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        dump('Erreur upload fichier:', $e->getMessage());
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image: ' . $e->getMessage());
                        return $this->redirectToRoute('app_add_product');
                    }

                    $product->setImagePath($newFilename);
                }

                // Ajout de la date de création
                $product->setCreatedAt(new \DateTimeImmutable());
                
                dump('Produit avant persist:', $product);
                
                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success', 'Le produit a été ajouté avec succès');
                return $this->redirectToRoute('app_admin_products');
            } catch (\Exception $e) {
                // Débogage détaillé de l'erreur
                dump('Erreur détaillée:', [
                    'message' => $e->getMessage(),
                    'fichier' => $e->getFile(),
                    'ligne' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du produit: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Débogage des erreurs de validation du formulaire
            dump('Erreurs de validation:', $form->getErrors(true));
        }

        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/product/edit/{id}', name: 'app_edit_product', methods: ['GET', 'POST'])]
    public function editProduct(
        Request $request, 
        Product $product,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gestion de l'image
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    // Suppression de l'ancienne image si elle existe
                    if ($product->getImagePath()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/images/products/' . $product->getImagePath();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('products_directory'),
                            $newFilename
                        );
                        $product->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                        return $this->redirectToRoute('app_edit_product', ['id' => $product->getId()]);
                    }
                }

                $entityManager->flush();
                $this->addFlash('success', 'Le produit a été modifié avec succès');
                return $this->redirectToRoute('app_admin_products');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du produit');
            }
        }

        return $this->render('admin/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/admin/product/delete/{id}', name: 'app_delete_product', methods: ['POST'])]
    public function deleteProduct(
        int $id, 
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse
    {
        try {
            // Vérification du jeton CSRF
            if (!$this->isCsrfTokenValid('delete-product', $request->headers->get('X-CSRF-TOKEN'))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], 403);
            }

            $product = $productRepository->find($id);
            
            if (!$product) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Produit non trouvé'
                ], 404);
            }

            // Suppression de l'image associée si elle existe
            if ($product->getImagePath()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/products/' . $product->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true, 
                'message' => 'Le produit a été supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detailProduct(Product $product): Response
    {
        return $this->render('product/details.html.twig', [  // ← C'est ce fichier
            'product' => $product,
            'relatedProducts' => $this->productRepository->findBy(
                ['type' => $product->getType()],
                ['createdAt' => 'DESC'],
                4
            )
        ]);
    }

    #[Route('/api/product/rate', name: 'app_product_rate', methods: ['POST'])]
    public function rateProduct(Request $request, ReviewRepository $reviewRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'] ?? null;
        $rating = $data['rating'] ?? null;
        
        if (!$productId || !$rating) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }
        
        try {
            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new NotFoundHttpException('Produit non trouvé');
            }
            
            // Vérifier si l'utilisateur a déjà noté ce produit
            $existingReview = $reviewRepository->findOneBy([
                'user' => $this->getUser(),
                'product' => $product
            ]);

            if ($existingReview) {
                // Mettre à jour la note existante
                $existingReview->setRating((int)$rating);
                $reviewRepository->save($existingReview, true);
            } else {
                // Créer une nouvelle note
                $review = new Review();
                $review->setUser($this->getUser());
                $review->setProduct($product);
                $review->setRating((int)$rating);
                $review->setCreatedAt(new \DateTimeImmutable());
                
                $reviewRepository->save($review, true);
            }
            
            // Calculer la nouvelle moyenne
            $reviews = $reviewRepository->findBy(['product' => $product]);
            $total = 0;
            $count = count($reviews);
            
            foreach ($reviews as $review) {
                $total += $review->getRating();
            }
            
            $newAverage = $count > 0 ? $total / $count : 0;
            
            // Mettre à jour la note moyenne du produit
            $product->setRating($newAverage);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'newRating' => round($newAverage, 1)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue'], 500);
        }
    }
}
