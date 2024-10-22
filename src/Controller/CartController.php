<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        // Simulons quelques produits dans le panier
        $cartItems = [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'price' => 349.99, 'quantity' => 1],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'price' => 699.99, 'quantity' => 1],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'price' => 129.99, 'quantity' => 2],
        ];

        $total = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cartItems));

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }
}
