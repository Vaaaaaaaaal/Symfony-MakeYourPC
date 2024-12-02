<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $ordersQuery = $entityManager->createQueryBuilder()
                ->select('o', 'i', 'p', 's')
                ->from('App\Entity\Order', 'o')
                ->leftJoin('o.items', 'i')
                ->leftJoin('i.product', 'p')
                ->leftJoin('o.shipping', 's')
                ->where('o.user = :user')
                ->setParameter('user', $user)
                ->orderBy('o.createdAt', 'DESC')
                ->getQuery();

            $rawOrders = $ordersQuery->getResult();
            $logger->info('Nombre de commandes trouvées : ' . count($rawOrders));

            $orders = [];
            foreach ($rawOrders as $order) {
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

                $orders[] = $orderData;
            }

            $logger->info('Données formatées : ' . json_encode($orders));

            return $this->render('profile/index.html.twig', [
                'user' => $user,
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            $logger->error('Erreur lors du traitement des commandes : ' . $e->getMessage());
            $logger->error('Trace : ' . $e->getTraceAsString());
            
            return $this->render('profile/index.html.twig', [
                'user' => $user,
                'orders' => []
            ]);
        }
    }
} 