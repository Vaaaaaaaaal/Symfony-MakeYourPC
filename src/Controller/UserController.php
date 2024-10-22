<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController extends AbstractController
{
    #[Route('/login/success', name: 'app_login_success')]
    public function loginSuccess(Security $security): Response
    {
        // Vérifiez si l'utilisateur est connecté
        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $security->getUser();
        
        // Redirigez en fonction du rôle de l'utilisateur
        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        } else {
            return $this->redirectToRoute('app_home');
        }
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

    #[Route('/profile', name: 'app_profile')]
    public function profile(UserInterface $user, UserRepository $userRepository): Response
    {
        // Récupérer l'utilisateur complet depuis la base de données
        $fullUser = $userRepository->find($user->getId());

        // Simulons l'historique des commandes (à remplacer par de vraies données plus tard)
        $orders = [
            ['id' => 1, 'date' => '2023-05-01', 'total' => 599.99, 'image' => 'i7.png'],
            ['id' => 2, 'date' => '2023-06-15', 'total' => 1299.99,  'image' => 'rtx.jpg'],
            ['id' => 3, 'date' => '2023-07-20', 'total' => 799.99,  'image' => 'ssd.avif'],
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $fullUser,
            'orders' => $orders,
        ]);
    }
}
