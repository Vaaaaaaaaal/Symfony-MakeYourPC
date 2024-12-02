<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 *
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private CartItemRepository $cartItemRepository
    ) {
        parent::__construct($registry, Cart::class);
    }

    public function findOrCreateCart(User $user): Cart
    {
        $cart = $this->findOneBy(['user' => $user]);
        
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->getEntityManager()->persist($cart);
            $this->getEntityManager()->flush();
        }
        
        return $cart;
    }

    public function addProductToCart(Cart $cart, Product $product, int $quantity): CartItem
    {
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);

        if ($cartItem) {
            $cartItem->setQuantity($quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart)
                    ->setProduct($product)
                    ->setQuantity($quantity);
            $this->getEntityManager()->persist($cartItem);
        }

        $this->getEntityManager()->flush();
        
        return $cartItem;
    }

    public function updateCartItemQuantity(Cart $cart, Product $product, int $change): ?CartItem
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
        $this->getEntityManager()->flush();
        
        return $cartItem;
    }

    public function removeProductFromCart(Cart $cart, Product $product): bool
    {
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);
        
        if (!$cartItem) {
            return false;
        }

        $this->getEntityManager()->remove($cartItem);
        $this->getEntityManager()->flush();
        
        return true;
    }

    public function getCartTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getItems() as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }
        return $total;
    }

    public function getCartItemsCount(Cart $cart): int
    {
        return array_reduce(
            $cart->getItems()->toArray(),
            function($sum, $item) {
                return $sum + $item->getQuantity();
            },
            0
        );
    }

    public function save(Cart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

