<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Address;
use App\Entity\OrderItem;
use App\Entity\OrderShipping;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderManager
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager
    ) {}

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

    public function getDashboardStats(): array
    {
        $orders = $this->orderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentOrders = array_map(function($order) {
            return [
                'id' => $order->getId(),
                'date' => $order->getCreatedAt(),
                'total' => $order->getTotalAmount(),
                'status' => $order->getStatus()
            ];
        }, $orders);

        return [
            'orderCount' => $this->orderRepository->count([]),
            'totalRevenue' => $this->orderRepository->getTotalRevenue(),
            'recentOrders' => $recentOrders
        ];
    }

    public function getUserOrders(User $user): array
    {
        $orders = $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        return array_map(function($order) {
            return [
                'id' => $order->getId(),
                'date' => $order->getCreatedAt()->format('d/m/Y H:i'),
                'totalAmount' => $order->getTotalAmount(),
                'items' => $order->getItems(),
                'shipping' => $order->getShipping()
            ];
        }, $orders);
    }

    public function getOrder(int $orderId): ?Order
    {
        return $this->orderRepository->find($orderId);
    }

    private function formatOrdersData(array $orders): array
    {
        return array_map(function($order) {
            return [
                'id' => $order->getId(),
                'date' => $order->getCreatedAt(),
                'total' => $order->getTotal(),
                'status' => $order->getStatus(),
                'items' => array_map(function($item) {
                    return [
                        'product' => $item->getProduct()->getName(),
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getPrice()
                    ];
                }, $order->getItems()->toArray())
            ];
        }, $orders);
    }
} 