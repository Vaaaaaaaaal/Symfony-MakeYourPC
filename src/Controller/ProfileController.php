<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        // Simulons les données de l'utilisateur
        $user = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Rue de la Paix, 75000 Paris',
            'phone' => '01 23 45 67 89',
        ];

        // Simulons l'historique des commandes
        $orders = [
            ['id' => 1, 'date' => '2023-05-01', 'total' => 599.99, 'image' => 'i7.png'],
            ['id' => 2, 'date' => '2023-06-15', 'total' => 1299.99,  'image' => 'rtx.jpg'],
            ['id' => 3, 'date' => '2023-07-20', 'total' => 799.99,  'image' => 'ssd.avif'],
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }
}
