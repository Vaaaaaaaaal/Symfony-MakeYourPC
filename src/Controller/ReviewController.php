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
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $content = $request->getContent();
        $data = json_decode($content, true);
        
        // Log pour déboguer
        $this->logger->info('Contenu reçu:', ['content' => $content, 'data' => $data]);
        
        $productId = $data['productId'] ?? null;
        $rating = $data['rating'] ?? null;
        
        if (!$productId || !$rating) {
            // Log pour déboguer
            $this->logger->error('Données manquantes:', [
                'productId' => $productId,
                'rating' => $rating
            ]);
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }
        
        try {
            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new NotFoundHttpException('Produit non trouvé');
            }
            
            $existingReview = $this->reviewRepository->findOneBy([
                'user' => $this->getUser(),
                'product' => $product
            ]);

            if ($existingReview) {
                $existingReview->setRating((int)$rating);
                $this->reviewRepository->save($existingReview, true);
                $this->logger->info('Review existante mise à jour');
            } else {
                $review = new Review();
                $review->setUser($this->getUser());
                $review->setProduct($product);
                $review->setRating((int)$rating);
                $review->setCreatedAt(new \DateTimeImmutable());
                
                $this->reviewRepository->save($review, true);
                $this->logger->info('Nouvelle review créée');
            }
            
            $newAverage = $this->reviewRepository->getAverageRating($product);
            $product->setRating($newAverage);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'newRating' => $newAverage
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => 'Une erreur est survenue'], 500);
        }
    }
} 