<?php

namespace App\Service;

use App\Entity\CartItem;
use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartItemManager
{
    public function __construct(
        private CartItemRepository $cartItemRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getItemTotal(CartItem $cartItem): float
    {
        return $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
    }

    public function updateQuantity(CartItem $cartItem, int $quantity): void
    {
        if ($quantity > $cartItem->getProduct()->getStock()) {
            throw new \Exception('Stock insuffisant');
        }
        
        $cartItem->setQuantity($quantity);
        $this->entityManager->flush();
    }

    public function removeCartItem(CartItem $cartItem): void
    {
        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
    }

    public function checkStockAvailability(CartItem $cartItem): bool
    {
        return $cartItem->getQuantity() <= $cartItem->getProduct()->getStock();
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->cartItemRepository->findByCartAndProduct($cart, $product);
    }

    public function findItemsByCart(Cart $cart): array
    {
        return $this->cartItemRepository->findItemsByCart($cart);
    }

    public function createCartItem(Cart $cart, Product $product, int $quantity): CartItem
    {
        $cartItem = new CartItem();
        $cartItem->setCart($cart)
                 ->setProduct($product)
                 ->setQuantity($quantity);
        
        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();
        
        return $cartItem;
    }
} 