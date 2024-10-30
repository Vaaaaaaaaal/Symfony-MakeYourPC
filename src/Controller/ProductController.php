<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Form\ProductType;
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

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(Request $request): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'price' => 349.99, 'image' => 'i7.png', 'type' => 'cpu', 'rating' => 4.5],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'price' => 699.99, 'image' => 'rtx.jpg', 'type' => 'gpu', 'rating' => 4.8],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'price' => 129.99, 'image' => 'ssd.avif', 'type' => 'ssd', 'rating' => 4.2],
            ['id' => 4, 'name' => 'Carte mère ASUS ROG', 'price' => 249.99, 'image' => 'motherboard.png', 'type' => 'motherboard', 'rating' => 4.0],
        ];

        $filteredProducts = $this->filterProducts($products, $request);

        return $this->render('product/index.html.twig', [
            'products' => $filteredProducts,
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
}
