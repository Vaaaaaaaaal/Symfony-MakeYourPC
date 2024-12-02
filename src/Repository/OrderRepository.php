<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrdersWithDetailsByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'i', 'p', 's')
            ->innerJoin('o.items', 'i')
            ->innerJoin('i.product', 'p')
            ->innerJoin('o.shipping', 's')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function formatOrdersData(array $orders): array
    {
        return array_map(function($order) {
            $orderData = [
                'id' => $order->getId(),
                'date' => $order->getCreatedAt()->format('d/m/Y H:i'),
                'total' => $order->getTotalAmount(),
                'items' => [],
                'shipping' => null
            ];

            foreach ($order->getItems() as $item) {
                $orderData['items'][] = [
                    'product' => [
                        'name' => $item->getProduct()->getName()
                    ],
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice()
                ];
            }

            if ($order->getShipping()) {
                $shipping = $order->getShipping();
                $orderData['shipping'] = [
                    'firstName' => $shipping->getFirstName(),
                    'lastName' => $shipping->getLastName(),
                    'address' => $shipping->getAddress(),
                    'postalCode' => $shipping->getPostalCode(),
                    'city' => $shipping->getCity()
                ];
            }

            return $orderData;
        }, $orders);
    }
}

