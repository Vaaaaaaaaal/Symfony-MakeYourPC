<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        
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
                $total += $item->getProduct()->getPrice() * $item->getQuantity();
            }
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function addToCart(
        int $id, 
        Request $request, 
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $newQuantity = $data['quantity'] ?? 1;

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }

            $product = $productRepository->find($id);
            if (!$product) {
                throw new \Exception('Produit non trouvé');
            }

            // Vérifier si la nouvelle quantité est disponible en stock
            if ($product->getStock() < $newQuantity) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'availableStock' => $product->getStock()
                ], 400);
            }

            $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $entityManager->persist($cart);
            }

            $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
                'cart' => $cart,
                'product' => $product
            ]);

            if ($cartItem) {
                // Mettre à jour directement avec la nouvelle quantité
                $cartItem->setQuantity($newQuantity);
            } else {
                // Créer un nouveau CartItem
                $cartItem = new CartItem();
                $cartItem->setCart($cart)
                        ->setProduct($product)
                        ->setQuantity($newQuantity);
                $entityManager->persist($cartItem);
            }

            $entityManager->flush();

            // Calculer la quantité totale dans le panier
            $totalQuantity = array_reduce(
                $cart->getItems()->toArray(),
                function($sum, $item) {
                    return $sum + $item->getQuantity();
                },
                0
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Panier mis à jour',
                'cartCount' => $totalQuantity,
                'stockRemaining' => $product->getStock() - $newQuantity
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
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
            }

            $data = json_decode($request->getContent(), true);
            $change = $data['change'] ?? 0;

            $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            $product = $productRepository->find($id);
            
            $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
                'cart' => $cart,
                'product' => $product
            ]);

            if (!$cartItem) {
                return new JsonResponse(['success' => false, 'message' => 'Item non trouvé'], 404);
            }

            $newQuantity = $cartItem->getQuantity() + $change;
            
            // Vérifier le stock disponible
            if ($newQuantity > $product->getStock()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'availableStock' => $product->getStock()
                ], 400);
            }
            
            // Empêcher la quantité d'être inférieure à 1
            if ($newQuantity < 1) {
                $newQuantity = 1;
            }
            
            $cartItem->setQuantity($newQuantity);
            $entityManager->flush();

            // Calculer les nouveaux totaux
            $itemTotal = $cartItem->getProduct()->getPrice() * $newQuantity;
            $total = 0;
            foreach ($cart->getItems() as $item) {
                $total += $item->getProduct()->getPrice() * $item->getQuantity();
            }

            return new JsonResponse([
                'success' => true,
                'quantity' => $newQuantity,
                'itemTotal' => $itemTotal,
                'total' => $total,
                'itemCount' => count($cart->getItems()),
                'stockRemaining' => $product->getStock() - $newQuantity
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeFromCart(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
            }

            $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            $product = $productRepository->find($id);
            
            $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
                'cart' => $cart,
                'product' => $product
            ]);

            if (!$cartItem) {
                return new JsonResponse(['success' => false, 'message' => 'Item non trouvé'], 404);
            }

            // Supprimer l'item
            $entityManager->remove($cartItem);
            $entityManager->flush();

            // Recalculer le total après la suppression
            $total = 0;
            foreach ($cart->getItems() as $item) {
                $total += $item->getProduct()->getPrice() * $item->getQuantity();
            }

            // Compter uniquement le nombre de produits différents
            $itemCount = count($cart->getItems());

            return new JsonResponse([
                'success' => true,
                'total' => $total,
                'cartCount' => $itemCount,
                'message' => 'Produit supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression'
            ], 500);
        }
    }

    private function isDuplicateRequest(?string $requestId): bool
    {
        if (!$requestId) {
            return false;
        }

        $cache = $this->cache; // Injectez le service de cache
        $key = 'request_' . $requestId;
        
        if ($cache->has($key)) {
            return true;
        }
        
        $cache->set($key, true, 60); // Cache pour 60 secondes
        return false;
    }
}
