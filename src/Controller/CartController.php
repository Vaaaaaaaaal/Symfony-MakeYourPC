<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CartManager;
use App\Service\UserManager;
use Psr\Log\LoggerInterface;
use App\Service\ProductManager;

class CartController extends AbstractController
{
    public function __construct(
        private CartManager $cartManager,
        private ProductManager $productManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $user = $this->getUser();
        $cart = $this->cartManager->getOrCreateCart($user);
        
        $cartItems = [];
        $total = 0;
        
        if ($cart) {
            foreach ($cart->getItems() as $item) {
                $cartItems[] = [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'price' => $item->getProduct()->getPrice(),
                    'quantity' => $item->getQuantity(),
                    'image' => $item->getProduct()->getImagePath(),
                    'stock' => $item->getProduct()->getStock()
                ];
            }
            $total = $this->cartManager->getTotal($cart);
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function addToCart(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $quantity = $data['quantity'] ?? 1;

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }

            $product = $this->productManager->getProduct($id);
            if (!$product) {
                throw new \Exception('Produit non trouvé');
            }

            if ($product->getStock() < $quantity) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'availableStock' => $product->getStock()
                ], 400);
            }

            $cart = $this->cartManager->getOrCreateCart($user);
            $cartItem = $this->cartManager->addProduct($cart, $product, $quantity);

            return new JsonResponse([
                'success' => true,
                'message' => 'Panier mis à jour',
                'cartCount' => $this->cartManager->getItemsCount($cart),
                'stockRemaining' => $product->getStock() - $quantity
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function updateCart(
        int $id,
        Request $request
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Non autorisé');
            }

            $data = json_decode($request->getContent(), true);
            $change = $data['change'] ?? 0;

            $cart = $this->cartManager->getOrCreateCart($user);
            $cartItem = $this->cartManager->updateCartItemQuantity($cart, $id, $change);

            return new JsonResponse([
                'success' => true,
                'quantity' => $cartItem->getQuantity(),
                'itemTotal' => $cartItem->getTotal(),
                'total' => $cart->getTotal(),
                'itemCount' => $cart->getItemCount(),
                'stockRemaining' => $cartItem->getProduct()->getStock()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeFromCart(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Non autorisé');
            }

            $cart = $this->cartManager->getOrCreateCart($user);
            $product = $this->productManager->getProduct($id);
            
            if (!$this->cartManager->removeProduct($cart, $product)) {
                throw new \Exception('Item non trouvé');
            }

            return new JsonResponse([
                'success' => true,
                'total' => $this->cartManager->getTotal($cart),
                'cartCount' => $this->cartManager->getItemsCount($cart),
                'message' => 'Produit supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


}
