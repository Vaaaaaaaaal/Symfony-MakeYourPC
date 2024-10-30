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

    public function findByFilters(?string $search, ?float $priceMin, ?float $priceMax, ?string $type, ?float $rating): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search !== null && $search !== '') {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($priceMin !== null) {
            $qb->andWhere('p.price >= :priceMin')
               ->setParameter('priceMin', $priceMin);
        }

        if ($priceMax !== null) {
            $qb->andWhere('p.price <= :priceMax')
               ->setParameter('priceMax', $priceMax);
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($rating !== null) {
            $qb->andWhere('p.rating >= :rating')
               ->setParameter('rating', $rating);
        }

        return $qb->getQuery()->getResult();
    }
}
