<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartManager
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private EntityManagerInterface $entityManager
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
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);

        if ($cartItem) {
            $cartItem->setQuantity($quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart)
                    ->setProduct($product)
                    ->setQuantity($quantity);
            $this->entityManager->persist($cartItem);
        }

        $this->entityManager->flush();
        
        return $cartItem;
    }

    public function updateItemQuantity(Cart $cart, Product $product, int $change): ?CartItem
    {
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);
        
        if (!$cartItem) {
            return null;
        }

        $newQuantity = $cartItem->getQuantity() + $change;
        if ($newQuantity < 1) {
            $newQuantity = 1;
        }
        
        $cartItem->setQuantity($newQuantity);
        $this->entityManager->flush();
        
        return $cartItem;
    }

    public function removeProduct(Cart $cart, Product $product): bool
    {
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);
        
        if (!$cartItem) {
            return false;
        }

        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
        
        return true;
    }

    public function getTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getItems() as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }
        return $total;
    }

    public function getItemsCount(Cart $cart): int
    {
        return array_reduce(
            $cart->getItems()->toArray(),
            function($sum, $item) {
                return $sum + $item->getQuantity();
            },
            0
        );
    }
} 