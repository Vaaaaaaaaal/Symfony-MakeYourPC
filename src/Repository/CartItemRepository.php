<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 *
 * @method CartItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartItem[]    findAll()
 * @method CartItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function save(CartItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CartItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);
    }

    public function getItemTotal(CartItem $cartItem): float
    {
        return $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
    }

    public function updateQuantity(CartItem $cartItem, int $quantity): void
    {
        $cartItem->setQuantity($quantity);
        $this->getEntityManager()->flush();
    }

    public function removeItem(CartItem $cartItem): void
    {
        $this->getEntityManager()->remove($cartItem);
        $this->getEntityManager()->flush();
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

    public function checkStockAvailability(CartItem $cartItem): bool
    {
        return $cartItem->getQuantity() <= $cartItem->getProduct()->getStock();
    }
}

