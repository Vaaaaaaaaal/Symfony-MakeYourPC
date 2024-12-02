<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Address;
use App\Entity\OrderShipping;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

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
    public function __construct(
        ManagerRegistry $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, Order::class);
    }

    public function createOrderFromCart(Cart $cart, User $user): Order
    {
        $this->entityManager->beginTransaction();
        try {
            $order = new Order();
            $order->setUser($user);
            
            $total = 0;
            foreach ($cart->getItems() as $cartItem) {
                $product = $cartItem->getProduct();
                if ($product->getStock() < $cartItem->getQuantity()) {
                    throw new \Exception('Stock insuffisant pour ' . $product->getName());
                }

                $orderItem = new OrderItem();
                $orderItem->setOrder($order)
                         ->setProduct($product)
                         ->setQuantity($cartItem->getQuantity())
                         ->setPrice($product->getPrice());

                $newStock = $product->getStock() - $cartItem->getQuantity();
                $product->setStock($newStock);
                
                $this->entityManager->persist($orderItem);
                $this->entityManager->persist($product);
                
                $total += $product->getPrice() * $cartItem->getQuantity();
            }

            $order->setTotalAmount($total);
            $this->entityManager->persist($order);
            
            return $order;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function finalizeOrder(Order $order, Cart $cart): void
    {
        try {
            $this->entityManager->persist($order);
            $this->entityManager->remove($cart);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function createOrderShipping(Order $order, User $user): OrderShipping
    {
        $shipping = new OrderShipping();
        $shipping->setOrderRef($order)
                ->setFirstName($user->getName())
                ->setLastName($user->getSurname())
                ->setEmail($user->getEmail())
                ->setPhone($user->getTelephone() ?? '')
                ->setAddress($user->getAdresse() ?? '');
        
        return $shipping;
    }

    public function updateShippingFromAddress(OrderShipping $shipping, Address $address): void
    {
        $shipping->setFirstName($address->getFirstname())
                ->setLastName($address->getLastname())
                ->setAddress($address->getAddress())
                ->setPostalCode($address->getPostal())
                ->setCity($address->getCity())
                ->setPhone($address->getPhone());
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
            ->join('o.items', 'i')
            ->join('i.product', 'p')
            ->join('o.shipping', 's')
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

