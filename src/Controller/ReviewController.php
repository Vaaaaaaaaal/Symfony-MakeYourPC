<?php

namespace App\Controller;

use App\Service\ReviewManager;
use App\Service\ProductManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/review')]
class ReviewController extends AbstractController
{
    public function __construct(
        private ReviewManager $reviewManager,
        private ProductManager $productManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/rate', name: 'app_review_rate', methods: ['POST'])]
    public function rate(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }

            $data = json_decode($request->getContent(), true);
            $productId = $data['productId'] ?? null;
            $rating = $data['rating'] ?? null;

            if (!$productId || !$rating) {
                throw new \Exception('ProductId et rating sont requis');
            }

            $product = $this->productManager->getProduct($productId);
            $review = $this->reviewManager->createOrUpdateReview($product, $user, (float)$rating);
            $averageRating = $this->reviewManager->getAverageRating($product);

            return new JsonResponse([
                'success' => true,
                'message' => 'Avis enregistré avec succès',
                'averageRating' => $averageRating
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 