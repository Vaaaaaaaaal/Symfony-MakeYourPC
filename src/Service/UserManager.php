<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\OrderItem;

class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function updateProfile(User $user, ?string $plainPassword = null): void
    {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $this->userRepository->updateUserPassword($user, $hashedPassword);
        } else {
            $this->userRepository->updateUserProfile($user);
        }
    }

    public function updateRole(User $user, string $role): void
    {
        if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
            throw new \Exception('Rôle invalide');
        }
        
        $user->setIsAdmin($role === 'ROLE_ADMIN');
        $this->entityManager->flush();
    }

    public function deleteUser(User $user): void
    {
        try {
            if ($user->isAdmin()) {
                throw new \Exception('Impossible de supprimer un administrateur');
            }

            // Supprimer les commandes et leurs éléments associés
            $orders = $this->entityManager
                ->getRepository(Order::class)
                ->findBy(['user' => $user]);
            
            foreach ($orders as $order) {
                // Supprimer d'abord les order_items
                $orderItems = $this->entityManager
                    ->getRepository(OrderItem::class)
                    ->findBy(['order' => $order]);
                
                foreach ($orderItems as $orderItem) {
                    $this->entityManager->remove($orderItem);
                }
                // Puis supprimer la commande
                $this->entityManager->remove($order);
            }

            // Supprimer le panier et ses éléments
            $cart = $this->entityManager
                ->getRepository(Cart::class)
                ->findOneBy(['user' => $user]);

            if ($cart) {
                $cartItems = $this->entityManager
                    ->getRepository(CartItem::class)
                    ->findBy(['cart' => $cart]);

                foreach ($cartItems as $cartItem) {
                    $this->entityManager->remove($cartItem);
                }
                $this->entityManager->remove($cart);
            }

            // Supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage());
        }
    }

    public function getDashboardStats(): array
    {
        return [
            'users' => $this->userRepository->count(['isAdmin' => false]),
            'admins' => $this->userRepository->count(['isAdmin' => true])
        ];
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAllUsers();
    }

    public function updateUser(User $user): void
    {
        $this->userRepository->updateUserProfile($user);
    }

    public function canDeleteUser(User $currentUser, User $targetUser): bool
    {
        if ($currentUser === $targetUser) {
            return false;
        }

        if ($targetUser->isAdmin()) {
            return false;
        }

        return true;
    }

    public function getUserCount(): int
    {
        return $this->userRepository->count([]);
    }

    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }
} 