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
                'choices' => [
                    'Tous' => '',
                    'PC Fixe' => 'desktop',
                    'PC Portable' => 'laptop',
                    'Composants' => 'component'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('rating', IntegerType::class, [
                'required' => false,
                'label' => 'Note minimum',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 5
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Range([
                        'min' => 1,
                        'max' => 5
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->getForm();

        $products = $this->productManager->getAllProducts();
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView()
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detail(int $id): Response
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
                'cartQuantity' => $cartQuantity
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur : ' . $e->getMessage());
            throw $this->createNotFoundException('Le produit demandé n\'existe pas');
        }
    }
}
