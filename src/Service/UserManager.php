<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    public function updateRole(User $user, string $newRole): void
    {
        $isAdmin = $newRole === 'ROLE_ADMIN';
        $this->userRepository->updateUserRole($user, $isAdmin);
    }

    public function deleteUser(User $user): void
    {
        if ($user->isAdmin()) {
            throw new \Exception('Impossible de supprimer un administrateur');
        }
        $this->userRepository->deleteUser($user);
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
        $this->entityManager->persist($user);
        $this->entityManager->flush();
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
} 