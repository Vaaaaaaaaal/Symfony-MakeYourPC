<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySearchCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.type', 't')
            ->addSelect('t');

        if (isset($criteria['search'])) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (isset($criteria['price_min'])) {
            $qb->andWhere('p.price >= :price_min')
               ->setParameter('price_min', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $qb->andWhere('p.price <= :price_max')
               ->setParameter('price_max', $criteria['price_max']);
        }

        if (isset($criteria['type'])) {
            $qb->andWhere('t.id = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['rating'])) {
            $qb->andWhere('p.rating >= :rating')
               ->setParameter('rating', $criteria['rating']);
        }

        return $qb->getQuery()->getResult();
    }

    public function updateRating(Product $product, float $newRating): void
    {
        $product->setRating($newRating);
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }
}
