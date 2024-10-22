<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $cartItems = [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'price' => 349.99, 'image' => 'i7.png', 'quantity' => 1],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'price' => 699.99, 'image' => 'rtx.jpg', 'quantity' => 1],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'price' => 129.99, 'image' => 'ssd.avif', 'quantity' => 2],
        ];

        $total = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cartItems));

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $change = $data['change'] ?? 0;

        // Simulons la mise à jour du panier
        $cartItems = $this->getCartItems();
        foreach ($cartItems as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] += $change;
                if ($item['quantity'] <= 0) {
                    // Supprimez l'article si la quantité est 0 ou moins
                    $cartItems = array_filter($cartItems, function($i) use ($id) {
                        return $i['id'] != $id;
                    });
                }
                break;
            }
        }

        // Recalculez le total
        $total = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cartItems));

        // Trouvez l'article mis à jour
        $updatedItem = current(array_filter($cartItems, function($item) use ($id) {
            return $item['id'] == $id;
        }));

        return new JsonResponse([
            'success' => true,
            'itemId' => $id,
            'quantity' => $updatedItem['quantity'] ?? 0,
            'itemTotal' => ($updatedItem['price'] ?? 0) * ($updatedItem['quantity'] ?? 0),
            'subtotal' => $total,
            'total' => $total,
            'itemCount' => count($cartItems)
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id): JsonResponse
    {
        // Simulons la suppression de l'article du panier
        $cartItems = $this->getCartItems();
        $cartItems = array_filter($cartItems, function($item) use ($id) {
            return $item['id'] != $id;
        });

        // Recalculez le total
        $total = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cartItems));

        return new JsonResponse([
            'success' => true,
            'itemId' => $id,
            'subtotal' => $total,
            'total' => $total,
            'itemCount' => count($cartItems)
        ]);
    }

    private function getCartItems(): array
    {
        // Dans une vraie application, vous récupéreriez ces données depuis une session ou une base de données
        return [
            ['id' => 1, 'name' => 'Processeur Intel Core i7', 'price' => 349.99, 'quantity' => 1],
            ['id' => 2, 'name' => 'Carte graphique NVIDIA RTX 3080', 'price' => 699.99, 'quantity' => 1],
            ['id' => 3, 'name' => 'SSD Samsung 1To', 'price' => 129.99, 'quantity' => 2],
        ];
    }
}
