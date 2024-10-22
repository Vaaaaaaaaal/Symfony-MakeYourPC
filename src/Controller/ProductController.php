<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}
