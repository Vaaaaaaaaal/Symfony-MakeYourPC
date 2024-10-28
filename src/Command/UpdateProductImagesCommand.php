<?php

namespace App\Command;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateProductImagesCommand extends Command
{
    protected static $defaultName = 'app:update-product-images';

    private $productRepository;
    private $entityManager;

    public function __construct(ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $product = $this->productRepository->findOneBy(['name' => 'Intel Core i7-13700K']);
        
        if ($product) {
            $product->setImagePath('i7.png');
            $this->entityManager->flush();
            $io->success('Image mise à jour pour le produit Intel Core i7-13700K');
        } else {
            $io->error('Produit non trouvé');
        }

        return Command::SUCCESS;
    }
}
