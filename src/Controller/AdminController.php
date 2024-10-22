<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        // Simulons quelques statistiques
        $stats = [
            'users' => 1250,
            'orders' => 450,
            'revenue' => 125000,
            'products' => 75
        ];

        // Simulons quelques commandes récentes
        $recentOrders = [
            ['id' => 1, 'user' => 'John Doe', 'total' => 599.99, 'date' => '2023-07-25'],
            ['id' => 2, 'user' => 'Jane Smith', 'total' => 1299.99, 'date' => '2023-07-24'],
            ['id' => 3, 'user' => 'Bob Johnson', 'total' => 799.99, 'date' => '2023-07-23'],
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
