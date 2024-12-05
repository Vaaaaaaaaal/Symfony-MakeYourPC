<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductManager;
use App\Service\CartManager;
use App\Service\CartItemManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Form\ProductType;
use App\Service\ReviewManager;

class ProductController extends AbstractController
{
    public function __construct(
        private ProductManager $productManager,
        private LoggerInterface $logger,
        private CartManager $cartManager,
        private CartItemManager $cartItemManager
    ) {}

    #[Route('/admin/products', name: 'app_admin_products')]
    public function manageProducts(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/manage_products.html.twig', [
            'products' => $this->productManager->getAllProducts()
        ]);
    }

    #[Route('/admin/product/delete/{id}', name: 'app_delete_product', methods: ['POST'])]
    public function deleteProduct(
        Product $product,
        Request $request
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete-product', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide'
            ], 400);
        }

        try {
            $this->productManager->deleteProduct($product);
            return new JsonResponse([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du produit'
            ], 500);
        }
    }

    #[Route('/products', name: 'app_products')]
    public function index(Request $request): Response
    {
        $types = $this->productManager->getAllTypes();
        
        $typeChoices = ['Tous' => ''];
        foreach ($types as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

        $form = $this->createFormBuilder()
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Rechercher',
                'attr' => ['class' => 'form-control']
            ])
            ->add('price_min', NumberType::class, [
                'required' => false,
                'label' => 'Prix minimum',
                'attr' => ['class' => 'form-control']
            ])
            ->add('price_max', NumberType::class, [
                'required' => false,
                'label' => 'Prix maximum',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'label' => 'Type de produit',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-control']
            ])
            ->add('rating', IntegerType::class, [
                'required' => false,
                'label' => 'Note minimum',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 5
                ]
            ])
            ->getForm();

        $form->handleRequest($request);
        
        $criteria = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            if (!empty($data['search'])) {
                $criteria['search'] = $data['search'];
            }
            
            if (!empty($data['price_min'])) {
                $criteria['price_min'] = $data['price_min'];
            }
            
            if (!empty($data['price_max'])) {
                $criteria['price_max'] = $data['price_max'];
            }
            
            if (!empty($data['type'])) {
                $criteria['type'] = $data['type'];
            }
            
            if (!empty($data['rating'])) {
                $criteria['rating'] = $data['rating'];
            }
        }

        $products = $this->productManager->getFilteredProducts($criteria);
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView()
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detail(int $id, ReviewManager $reviewManager): Response
    {
        try {
            $product = $this->productManager->getProduct($id);
            $user = $this->getUser();
            $cartQuantity = 0;

            if ($user) {
                $cart = $this->cartManager->getOrCreateCart($user);
                $cartItem = $this->cartItemManager->findByCartAndProduct($cart, $product);
                $cartQuantity = $cartItem ? $cartItem->getQuantity() : 0;
            }

            return $this->render('product/detail.html.twig', [
                'product' => $product,
                'cartQuantity' => $cartQuantity,
                'reviewManager' => $reviewManager
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur : ' . $e->getMessage());
            throw $this->createNotFoundException('Le produit demandé n\'existe pas');
        }
    }

    #[Route('/admin/product/add', name: 'app_add_product')]
    public function addProduct(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();
                $this->productManager->saveProduct($product, $imageFile);
                $this->addFlash('success', 'Le produit a été ajouté avec succès');
                return $this->redirectToRoute('app_admin_products');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'ajout du produit : ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du produit : ' . $e->getMessage());
            }
        }

        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/product/edit/{id}', name: 'app_edit_product')]
    public function editProduct(Request $request, Product $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();
                $this->productManager->saveProduct($product, $imageFile);
                $this->addFlash('success', 'Le produit a été modifié avec succès');
                return $this->redirectToRoute('app_admin_products');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du produit');
            }
        }

        return $this->render('admin/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }
}
