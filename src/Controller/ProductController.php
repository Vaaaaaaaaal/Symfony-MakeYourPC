<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
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
    public function manageProducts(): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'type' => 'CPU', 'price' => 349.99, 'stock' => 50, 'image' => 'i7.png'],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'type' => 'GPU', 'price' => 699.99, 'stock' => 25, 'image' => 'rtx.jpg'],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'type' => 'SSD', 'price' => 129.99, 'stock' => 100, 'image' => 'ssd.avif'],
            ['id' => 4, 'name' => 'Carte mère ASUS ROG', 'type' => 'Motherboard', 'price' => 249.99, 'stock' => 30, 'image' => 'motherboard.png'],
        ];

        return $this->render('admin/manage_products.html.twig', [
            'products' => $products,
        ]);
    }



    #[Route('/admin/product/edit/{id}', name: 'app_edit_product')]
    public function editProduct(int $id): Response
    {
        // Simuler la modification d'un produit
        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/admin/product/delete/{id}', name: 'app_delete_product')]
    public function deleteProduct(int $id): Response
    {
        // Simuler la suppression d'un produit
        return $this->redirectToRoute('app_admin_products');
    }
}
