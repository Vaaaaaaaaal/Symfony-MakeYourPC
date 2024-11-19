<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\TypeRepository;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(Request $request, ProductRepository $productRepository, TypeRepository $typeRepository): Response
    {
        $search = $request->query->get('search');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $typeId = $request->query->get('type');
        $rating = $request->query->get('rating');

        $criteria = [];
        
        if ($search) {
            $criteria['search'] = $search;
        }
        
        if ($priceMin) {
            $criteria['price_min'] = $priceMin;
        }
        if ($priceMax) {
            $criteria['price_max'] = $priceMax;
        }
        
        if ($typeId) {
            $criteria['type'] = $typeId;
        }
        
        if ($rating) {
            $criteria['rating'] = $rating;
        }

        $products = $productRepository->findBySearchCriteria($criteria);
        $types = $typeRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'types' => $types
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detail(Product $product): Response
    {
        return $this->render('product/detail.html.twig', [
            'product' => $product
        ]);
    }
}
