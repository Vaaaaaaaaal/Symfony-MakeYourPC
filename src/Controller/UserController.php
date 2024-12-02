<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AbstractController
{
    #[Route('/login/success', name: 'app_login_success')]
    public function loginSuccess(Security $security): Response
    {
        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $security->getUser();
        
        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin'); 
        } else {
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/admin', name: 'app_admin')]
    public function adminDashboard(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orderCount = $entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from('App\Entity\Order', 'o')
            ->getQuery()
            ->getSingleScalarResult();

        $totalRevenue = $entityManager->createQueryBuilder()
            ->select('SUM(o.totalAmount)')
            ->from('App\Entity\Order', 'o')
            ->getQuery()
            ->getSingleScalarResult() ?? 0.00;

        $recentOrders = $entityManager->createQueryBuilder()
            ->select('o, u')
            ->from('App\Entity\Order', 'o')
            ->join('o.user', 'u')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $stats = [
            'users' => $userRepository->count(['isAdmin' => false]),
            'orders' => $orderCount,
            'revenue' => $totalRevenue,
            'products' => $productRepository->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function manageUsers(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }

<<<<<<< HEAD
=======
    #[Route('/profile', name: 'app_profile')]
    public function profile(Security $security): Response
    {
        $user = $security->getUser();
        
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
>>>>>>> 4fc1b50709ed1167737bebce28ba0cf3e5872b38

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(
        Request $request,
        Security $security,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $security->getUser();

        $form = $this->createFormBuilder($user)
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('surname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control']
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouveau mot de passe',
                'invalid_message' => ' ', 
                'attr' => [
                    'class' => 'form-control password-input',
                    'autocomplete' => 'new-password'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer les modifications',
                'attr' => ['class' => 'btn-save']  
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            try {
                $entityManager->flush();
                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
            }
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateUserRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($user === $security->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre rôle'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $newRole = $data['role'] ?? null;

        if (!$newRole) {
            return new JsonResponse(['success' => false, 'message' => 'Rôle non spécifié'], 400);
        }

        try {
            $user->setIsAdmin($newRole === 'ROLE_ADMIN');
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rôle'
            ], 500);
        }
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit', methods: ['POST'])]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);

        try {
            if (isset($data['name'])) $user->setName($data['name']);
            if (isset($data['surname'])) $user->setSurname($data['surname']);
            if (isset($data['email'])) $user->setEmail($data['email']);
            if (isset($data['adresse'])) $user->setAdresse($data['adresse']);
            if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
            if (!empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur'
            ], 500);
        }
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete', methods: ['DELETE'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($user === $security->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        if ($user->isAdmin()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Impossible de supprimer un administrateur'
            ], 403);
        }

        try {
            $entityManager->remove($user);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur'
            ], 500);
        }
    }

    #[Route('/admin/products', name: 'app_admin_products')]
    public function manageProducts(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $products = $productRepository->findAll();
        
        return $this->render('admin/manage_products.html.twig', [
            'products' => $products
        ]);
    }
}