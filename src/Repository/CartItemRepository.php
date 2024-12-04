<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Cart;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);
    }

    public function findItemsByCart(Cart $cart): array
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.cart = :cart')
            ->setParameter('cart', $cart)
            ->leftJoin('ci.product', 'p')
            ->addSelect('p')
            ->getQuery()
            ->getResult();
    }
}

