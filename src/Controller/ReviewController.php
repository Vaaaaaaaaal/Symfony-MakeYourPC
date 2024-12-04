<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\ReviewManager;
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

            $review = $this->reviewManager->createOrUpdateReview($product, $user, (float)$rating);

            return new JsonResponse([
                'success' => true,
                'message' => 'Avis enregistré avec succès',
                'averageRating' => $this->reviewManager->getAverageRating($product)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement de l\'avis : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 