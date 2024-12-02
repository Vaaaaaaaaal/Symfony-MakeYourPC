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

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(Request $request, ProductRepository $productRepository, TypeRepository $typeRepository): Response
    {
        $search = $request->query->get('search');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $typeId = $request->query->get('type');
        $rating = $request->query->get('rating');

        $criteria = [];
        
        if ($search) {
            $criteria['search'] = $search;
        }
        
        if ($priceMin) {
            $criteria['price_min'] = $priceMin;
        }
        if ($priceMax) {
            $criteria['price_max'] = $priceMax;
        }
        
        if ($typeId) {
            $criteria['type'] = $typeId;
        }
        
        if ($rating) {
            $criteria['rating'] = $rating;
        }

        $products = $productRepository->findBySearchCriteria($criteria);
        $types = $typeRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'types' => $types
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detail(Product $product, Request $request): Response
    {
        // Récupérer la quantité dans le panier pour ce produit
        $cartQuantity = 0;
        
        // Si l'utilisateur est connecté
        if ($this->getUser()) {
            $cart = $this->getUser()-> getCart();
            if ($cart) {
                $cartItem = $cart->getItems()->filter(function($item) use ($product) {
                    return $item->getProduct()->getId() === $product->getId();
                })->first();
                
                if ($cartItem) {
                    $cartQuantity = $cartItem->getQuantity();
                }
            }
        } 
        // Si l'utilisateur n'est pas connecté, vérifier le panier en session
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
                // Définir une image par défaut si aucune image n'est uploadée
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
        
        // Logique de confirmation ici
        
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
                    
                    // Supprimer l'ancienne image si elle existe
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
}
