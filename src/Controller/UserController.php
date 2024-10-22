<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractController
{
    #[Route('/login_success', name: 'app_login_success')]
    public function loginSuccess(Security $security): Response
    {
        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        // Redirection par défaut pour les utilisateurs non-admin
        return $this->redirectToRoute('app_home');
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function adminDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Ajoutez ici la logique pour récupérer les statistiques et les données nécessaires
        $stats = [
            'users' => 0, // À remplacer par les vraies données
            'orders' => 0,
            'revenue' => 0,
            'products' => 0,
        ];
        $recentOrders = []; // À remplacer par les vraies données

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
