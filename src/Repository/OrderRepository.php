<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findOrdersWithDetailsByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'i', 'p', 's')
            ->join('o.items', 'i')
            ->join('i.product', 'p')
            ->join('o.shipping', 's')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAdminDashboardStats(): array
    {
        $orderCount = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalRevenue = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0.00;

        $recentOrders = $this->createQueryBuilder('o')
            ->select('o, u')
            ->join('o.user', 'u')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return [
            'orderCount' => $orderCount,
            'totalRevenue' => $totalRevenue,
            'recentOrders' => $recentOrders
        ];
    }
}

