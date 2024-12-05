<?php

namespace App\Controller;

use App\Service\UserManager;
use App\Service\OrderManager;
use App\Service\ProductManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    public function __construct(
        private UserManager $userManager,
        private OrderManager $orderManager,
        private ProductManager $productManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/admin', name: 'app_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        $stats = [
            'users' => $this->userManager->getUserCount(),
            'products' => $this->productManager->getProductCount(),
            'orderCount' => $this->orderManager->getOrderCount(),
            'totalRevenue' => $this->orderManager->getTotalRevenue()
        ];

        $recentOrders = $this->orderManager->getRecentOrders(5);

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function manageUsers(): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $this->userManager->getAllUsers()
        ]);
    }
} 