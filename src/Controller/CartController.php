<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartItems[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'image' => $product->getImagePath(),
                    'quantity' => $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, SessionInterface $session, ProductRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $change = $data['change'] ?? 0;
        
        $cart = $session->get('cart', []);
        
        if (isset($cart[$id])) {
            $cart[$id] += $change;
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }
        }
        
        $session->set('cart', $cart);
        
        // Recalculer le total
        $total = 0;
        $cartItems = [];
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $total += $product->getPrice() * $quantity;
                if ($productId == $id) {
                    $itemTotal = $product->getPrice() * $quantity;
                }
            }
        }
        
        return new JsonResponse([
            'success' => true,
            'itemId' => $id,
            'quantity' => $cart[$id] ?? 0,
            'itemTotal' => $itemTotal ?? 0,
            'total' => $total,
            'itemCount' => array_sum($cart)
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, SessionInterface $session, ProductRepository $productRepository): JsonResponse
    {
        $cart = $session->get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
        }
        
        $session->set('cart', $cart);
        
        // Recalculer le total
        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $total += $product->getPrice() * $quantity;
            }
        }
        
        return new JsonResponse([
            'success' => true,
            'total' => $total,
            'itemCount' => array_sum($cart)
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function addToCart(int $id, Request $request, SessionInterface $session, ProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->find($id);
        
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        $cart = $session->get('cart', []);
        
        if (isset($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        
        $session->set('cart', $cart);
        
        $cartCount = array_sum($cart);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'cartCount' => $cartCount
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
