<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/review')]
class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReviewRepository $reviewRepository,
        private ProductRepository $productRepository,
        private LoggerInterface $logger
    ) {}

    #[Route('/rate', name: 'app_review_rate', methods: ['POST'])]
    public function rate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \Exception('Données JSON invalides');
            }

            $productId = $data['productId'] ?? null;
            $rating = $data['rating'] ?? null;

            if (!$productId || !$rating) {
                throw new \Exception('ProductId et rating sont requis');
            }

            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new \Exception('Produit non trouvé');
            }

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }

            $review = $this->reviewRepository->findOneBy([
                'product' => $product,
                'user' => $user
            ]);

            if (!$review) {
                $review = new Review();
                $review->setProduct($product);
                $review->setUser($user);
                $review->setCreatedAt(new \DateTimeImmutable());
            }
            
            $review->setRating((float)$rating);
            $this->entityManager->persist($review);
            $this->entityManager->flush();

            $newAverageRating = $this->reviewRepository->getAverageRating($product);
            $product->setRating($newAverageRating);
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'newRating' => number_format($newAverageRating, 1)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
} 