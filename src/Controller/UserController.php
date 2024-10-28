<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;

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
            return $this->redirectToRoute('app_admin_dashboard');  // Correction ici
        } else {
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/admin', name: 'app_admin')]
    public function adminDashboard(
        UserRepository $userRepository,
        ProductRepository $productRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = [
            'users' => $userRepository->count(['isAdmin' => false]),
            'orders' => 0,
            'revenue' => 0.00,
            'products' => $productRepository->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function manageUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
}
