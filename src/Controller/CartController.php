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
                    'image' => $item->getProduct()->getImagePath()
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
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }

            $product = $productRepository->find($id);
            if (!$product) {
                throw new \Exception('Produit non trouvé');
            }

            // Débogage
            dump([
                'user' => $user,
                'product_id' => $product->getId(),
                'product_name' => $product->getName()
            ]);

            $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $entityManager->persist($cart);
                
                // Débogage
                dump('Nouveau panier créé');
            }

            $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
                'cart' => $cart,
                'product' => $product
            ]);

            if ($cartItem) {
                $cartItem->setQuantity($cartItem->getQuantity() + 1);
                dump('Quantité mise à jour');
            } else {
                $cartItem = new CartItem();
                $cartItem->setCart($cart)
                        ->setProduct($product)
                        ->setQuantity(1);
                $entityManager->persist($cartItem);
                dump('Nouvel item ajouté');
            }

            $entityManager->flush();
            dump('Sauvegarde effectuée');

            return new JsonResponse([
                'success' => true,
                'message' => 'Produit ajouté au panier',
                'cartCount' => count($cart->getItems())
            ]);
        } catch (\Exception $e) {
            dump('Erreur:', $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
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
                'itemCount' => count($cart->getItems())
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
}
