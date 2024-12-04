<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductManager
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private string $productsDirectory
    ) {}

    public function deleteProduct(Product $product): void
    {
        if ($product->getImagePath() && $product->getImagePath() !== 'default.png') {
            $imagePath = $this->productsDirectory . '/' . $product->getImagePath();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();
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
} 