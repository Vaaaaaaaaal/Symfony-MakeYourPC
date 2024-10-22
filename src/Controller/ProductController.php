<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'price' => 349.99, 'image' => 'i7.png', 'type' => 'cpu'],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'price' => 699.99, 'image' => 'rtx.jpg', 'type' => 'gpu'],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'price' => 129.99, 'image' => 'ssd.avif', 'type' => 'ssd'],
            ['id' => 4, 'name' => 'Carte mère ASUS ROG', 'price' => 249.99, 'image' => 'motherboard.png', 'type' => 'motherboard'],
        ];

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
}
