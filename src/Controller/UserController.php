<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Service\UserManager;
use App\Service\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserController extends AbstractController
{
    public function __construct(
        private UserManager $userManager,
        private OrderManager $orderManager,
        private LoggerInterface $logger
    ) {}



    #[Route('/admin/users', name: 'app_admin_users')]
    public function manageUsers(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/users.html.twig', [
            'users' => $this->userManager->getAllUsers()
        ]);
    }

    #[Route('/profile/edit', name: 'app_user_profile_edit')]
    public function editProfile(Request $request, Security $security): Response
    {
        /** @var User $user */
        $user = $security->getUser();

        $form = $this->createProfileForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $plainPassword = $form->get('plainPassword')->getData();
                $this->userManager->updateProfile($user, $plainPassword);
                return $this->redirectToRoute('app_user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function createProfileForm(User $user)
    {
        return $this->createFormBuilder($user)
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('surname', TextType::class, ['label' => 'Prénom'])
            ->add('email', EmailType::class)
            ->add('adresse', TextType::class, ['required' => false])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouveau mot de passe'
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $orders = $this->orderManager->getUserOrders($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'orders' => $orders
        ]);
    }

    #[Route('/admin/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateRole(int $id, Request $request): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            
            $data = json_decode($request->getContent(), true);
            $newRole = $data['role'] ?? null;
            
            if (!$newRole) {
                throw new \Exception('Le rôle est requis');
            }
            
            $user = $this->userManager->getUser($id);
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }
            
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                throw new \Exception('Utilisateur non connecté');
            }
            
            if ($user->getId() === $currentUser->getId()) {
                throw new \Exception('Vous ne pouvez pas modifier votre propre rôle');
            }
            
            $isAdmin = $newRole === 'ROLE_ADMIN';
            $user->setIsAdmin($isAdmin);
            $this->userManager->updateUser($user);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}