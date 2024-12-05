<?php

namespace App\Service;

use App\Entity\Review;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReviewManager
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createOrUpdateReview(Product $product, User $user, float $rating): Review
    {
        $review = $this->reviewRepository->findOneBy([
            'product' => $product,
            'user' => $user
        ]);

        if (!$review) {
            $review = new Review();
            $review->setProduct($product)
                  ->setUser($user)
                  ->setCreatedAt(new \DateTimeImmutable());
        }

        $review->setRating($rating);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function getAverageRating(Product $product): float
    {
        $reviews = $this->reviewRepository->findBy(['product' => $product]);
        if (empty($reviews)) {
            return 0;
        }

        $sum = 0;
        foreach ($reviews as $review) {
            $sum += $review->getRating();
        }

        $average = round($sum / count($reviews), 1);
        
        // Mise à jour du rating dans la table produit
        $product->setRating($average);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $average;
    }

    public function getProductReviews(Product $product): array
    {
        return $this->reviewRepository->findBy(
            ['product' => $product],
            ['createdAt' => 'DESC']
        );
    }

    public function getUserReviews(User $user): array
    {
        return $this->reviewRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function getUserReview(Product $product, User $user): ?Review
    {
        return $this->reviewRepository->findOneBy([
            'product' => $product,
            'user' => $user
        ]);
    }
} 