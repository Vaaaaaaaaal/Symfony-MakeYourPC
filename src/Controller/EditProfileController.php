<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_edit_profile')]
    public function index(): Response
    {
        // Simulons les données de l'utilisateur
        $user = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Rue de la Paix, 75000 Paris',
            'phone' => '01 23 45 67 89',
        ];

        return $this->render('edit_profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
