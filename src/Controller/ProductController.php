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

    #[Route('/admin/product/add', name: 'app_add_product')]
    public function addProduct(Request $request): Response
    {
        $form = $this->createForm(ProductType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Ici, vous traiteriez normalement les données pour les sauvegarder en base de données
            // Pour l'instant, nous allons simplement rediriger vers la page de gestion des produits
            
            $this->addFlash('success', 'Le produit a été ajouté avec succès.');
            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/product/edit/{id}', name: 'app_edit_product')]
    public function editProduct(int $id): Response
    {
        // Simuler la modification d'un produit
        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/admin/product/delete/{id}', name: 'app_delete_product', methods: ['POST'])]
    public function deleteProduct(
        int $id, 
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        try {
            $product = $productRepository->find($id);
            
            if (!$product) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Produit non trouvé'
                ], 404);
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
