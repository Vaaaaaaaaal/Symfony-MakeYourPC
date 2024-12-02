<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\TypeRepository;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProductType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Cart;
use App\Form\ProductFilterType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\CartRepository;

class ProductController extends AbstractController
{
    public function __construct(
        private CartRepository $cartRepository
    ) {}

    #[Route('/products', name: 'app_products')]
    public function index(Request $request, ProductRepository $productRepository, TypeRepository $typeRepository): Response
    {
        $form = $this->createForm(ProductFilterType::class, null, [
            'method' => 'GET'
        ]);
        
        $form->handleRequest($request);
        
        $criteria = [];
        if ($form->isSubmitted()) {
            $data = $form->getData();
            $criteria = [
                'search' => $data['search'] ?? null,
                'price_min' => $data['price_min'] ?? null,
                'price_max' => $data['price_max'] ?? null,
                'type' => $data['type'] ? $data['type']->getId() : null,
                'rating' => $data['rating'] ?? null
            ];
        }

        return $this->render('product/index.html.twig', [
            'form' => $form->createView(),
            'products' => $productRepository->findBySearchCriteria($criteria),
            'types' => $typeRepository->findAll(),
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detail(Product $product, Request $request): Response
    {
        $cartQuantity = 0;
        
        if ($this->getUser()) {
            $cart = $this->cartRepository->findOneBy(['user' => $this->getUser()]);
            if ($cart) {
                $cartItem = $cart->getItems()->filter(function($item) use ($product) {
                    return $item->getProduct()->getId() === $product->getId();
                })->first();
                
                if ($cartItem) {
                    $cartQuantity = $cartItem->getQuantity();
                }
            }
        } 
        else {
            $cart = $request->getSession()->get('cart', []);
            $cartQuantity = $cart[$product->getId()] ?? 0;
        }

        return $this->render('product/detail.html.twig', [
            'product' => $product,
            'cartQuantity' => $cartQuantity
        ]);
    }

    #[Route('/admin/product/add', name: 'app_add_product')]
    public function addProduct(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('products_directory'),
                        $newFilename
                    );
                    $product->setImagePath($newFilename);
                } catch (FileException $e) {
                    return $this->redirectToRoute('app_add_product');
                }
            } else {
                $product->setImagePath('default.png');
            }
            
            $entityManager->persist($product);
            $entityManager->flush();
            
            return $this->redirectToRoute('app_admin_products');
        }
        
        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/product/add/confirm', name: 'app_add_product_confirm')]
    public function confirmAddProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
                
        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/admin/product/{id}/edit', name: 'app_edit_product')]
    public function editProduct(
        Product $product, 
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('products_directory'),
                        $newFilename
                    );
                    
                    if ($product->getImagePath()) {
                        $oldImagePath = $this->getParameter('products_directory').'/'.$product->getImagePath();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $product->setImagePath($newFilename);
                } catch (FileException $e) {
                    return $this->redirectToRoute('app_edit_product', ['id' => $product->getId()]);
                }
            }
            
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_products');
        }
        
        return $this->render('admin/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/admin/products', name: 'app_admin_products')]
    public function manageProducts(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $products = $productRepository->findAll();
        
        return $this->render('admin/manage_products.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/admin/product/delete/{id}', name: 'app_delete_product', methods: ['POST'])]
    public function deleteProduct(
        Product $product,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete-product', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide'
            ], 400);
        }

        try {
            // Suppression de l'image associée si elle existe
            if ($product->getImagePath() && $product->getImagePath() !== 'default.png') {
                $imagePath = $this->getParameter('products_directory') . '/' . $product->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du produit'
            ], 500);
        }
    }
}
