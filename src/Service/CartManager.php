<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ProductManager;

class CartManager
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemManager $cartItemManager,
        private EntityManagerInterface $entityManager,
        private ProductManager $productManager
    ) {}

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }
        
        return $cart;
    }

    public function addProduct(Cart $cart, Product $product, int $quantity): CartItem
    {
        $cartItem = $this->cartItemManager->findByCartAndProduct($cart, $product);

        if (!$cartItem) {
            return $this->cartItemManager->createCartItem($cart, $product, $quantity);
        }

        $newQuantity = $cartItem->getQuantity() + $quantity;
        $this->cartItemManager->updateQuantity($cartItem, $newQuantity);

        return $cartItem;
    }

    public function updateCartItemQuantity(Cart $cart, int $productId, int $change): CartItem
    {
        $product = $this->productManager->getProduct($productId);
        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }

        $cartItem = $this->cartItemManager->findByCartAndProduct($cart, $product);
        if (!$cartItem) {
            throw new \Exception('Item non trouvé');
        }

        $newQuantity = $cartItem->getQuantity() + $change;
        $this->cartItemManager->updateQuantity($cartItem, $newQuantity);

        return $cartItem;
    }

    public function removeProduct(Cart $cart, Product $product): bool
    {
        $cartItem = $this->cartItemManager->findByCartAndProduct($cart, $product);
        
        if (!$cartItem) {
            return false;
        }

        $this->cartItemManager->removeCartItem($cartItem);
        return true;
    }

    public function getTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getItems() as $item) {
            $total += $this->cartItemManager->getItemTotal($item);
        }
        return $total;
    }

    public function getItemsCount(Cart $cart): int
    {
        return $cart->getItems()->count();
    }
} 