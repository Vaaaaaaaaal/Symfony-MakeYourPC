<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Review;

class ProductManager
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private string $productsDirectory,
        private TypeRepository $typeRepository
    ) {}

    public function deleteProduct(Product $product): void
    {
        try {
            // Supprimer l'image si elle existe
            if ($product->getImagePath() && $product->getImagePath() !== 'default.png') {
                $imagePath = $this->productsDirectory . '/' . $product->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Supprimer les reviews associées
            $reviews = $this->entityManager
                ->getRepository(Review::class)
                ->findBy(['product' => $product]);
                
            foreach ($reviews as $review) {
                $this->entityManager->remove($review);
            }

            // Supprimer le produit
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression du produit : ' . $e->getMessage());
        }
    }

    public function updateProduct(Product $product, ?UploadedFile $imageFile = null): void
    {
        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            $imageFile->move($this->productsDirectory, $newFilename);
            
            if ($product->getImagePath() && $product->getImagePath() !== 'default.png') {
                $oldImagePath = $this->productsDirectory . '/' . $product->getImagePath();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $product->setImagePath($newFilename);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function getAllProducts(): array
    {
        return $this->productRepository->findAll();
    }

    public function updateStock(Product $product, int $quantity): void
    {
        $newStock = $product->getStock() - $quantity;
        if ($newStock < 0) {
            throw new \Exception('Stock insuffisant');
        }
        $product->setStock($newStock);
        $this->entityManager->flush();
    }

    public function find(int $id)
    {
        return $this->productRepository->find($id);
    }

    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }
        return $product;
    }

    public function getAllTypes(): array
    {
        return $this->typeRepository->findAll();
    }

    public function getFilteredProducts(array $criteria): array
    {
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.type', 't');

        if (isset($criteria['search'])) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (isset($criteria['price_min'])) {
            $qb->andWhere('p.price >= :price_min')
               ->setParameter('price_min', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $qb->andWhere('p.price <= :price_max')
               ->setParameter('price_max', $criteria['price_max']);
        }

        if (isset($criteria['type']) && $criteria['type'] !== '') {
            $qb->andWhere('t.id = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['rating'])) {
            $qb->andWhere('p.rating >= :rating')
               ->setParameter('rating', $criteria['rating']);
        }

        return $qb->getQuery()->getResult();
    }

    public function getProductCount(): int
    {
        return $this->productRepository->count([]);
    }

    public function saveProduct(Product $product, ?UploadedFile $imageFile = null): void
    {
        try {
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move($this->productsDirectory, $newFilename);
                    
                    if ($product->getImagePath() && $product->getImagePath() !== 'default.png') {
                        $oldImagePath = $this->productsDirectory . '/' . $product->getImagePath();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $product->setImagePath($newFilename);
                } catch (\Exception $e) {
                    throw new \Exception('Erreur lors du traitement de l\'image : ' . $e->getMessage());
                }
            }

            if ($product->getId() === null) {
                $product->setCreatedAt(new \DateTime());
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la sauvegarde du produit : ' . $e->getMessage());
        }
    }
} 