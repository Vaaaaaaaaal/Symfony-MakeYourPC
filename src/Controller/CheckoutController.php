<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderShipping;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\CartManager;
use Psr\Log\LoggerInterface;
use App\Repository\OrderRepository;

class CheckoutController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CartManager $cartManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/checkout', name: 'app_checkout')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $user = $this->getUser();
            $cart = $this->cartManager->getOrCreateCart($user);

            if (!$cart || $cart->getItems()->isEmpty()) {
                return $this->redirectToRoute('app_cart');
            }

            $shipping = $this->orderRepository->createOrderShipping(new Order(), $user);
            
            $form = $this->createForm(CheckoutType::class, $shipping, [
                'user' => $user
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $selectedAddress = $form->get('savedAddress')->getData();
                    if ($selectedAddress) {
                        $this->orderRepository->updateShippingFromAddress($shipping, $selectedAddress);
                    }

                    $order = $this->orderRepository->createOrderFromCart($cart, $user);
                    $shipping->setOrderRef($order);
                    
                    $entityManager->persist($shipping);
                    $this->orderRepository->finalizeOrder($order, $cart);

                    return $this->redirectToRoute('app_checkout_confirmation');
                    
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du traitement : ' . $e->getMessage());
                    $this->addFlash('error', 'Une erreur est survenue lors de la création de la commande');
                }
            }

            return $this->render('checkout/index.html.twig', [
                'form' => $form->createView(),
                'cart' => $cart
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur générale : ' . $e->getMessage());
            return $this->redirectToRoute('app_cart');
        }
    }

    #[Route('/checkout/payment', name: 'app_checkout_payment')]
    public function payment(
        Request $request, 
        SessionInterface $session
    ): Response {
        if ($request->isMethod('POST')) {
            $orderId = $session->get('pending_order_id');
            $order = $this->orderRepository->find($orderId);

            if ($order) {
                $cart = $this->cartManager->getOrCreateCart($this->getUser());
                $this->orderRepository->finalizeOrder($order, $cart);
                $session->remove('cart');
                $session->remove('pending_order_id');

                return $this->redirectToRoute('app_order_confirmation');
            }
        }

        return $this->render('checkout/payment.html.twig');
    }

    #[Route('/checkout/confirmation', name: 'app_order_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('checkout/confirmation.html.twig');
    }

    #[Route('/admin/order/{id}/view', name: 'app_admin_order_view')]
    public function viewOrder(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/order/view.html.twig', [
            'order' => $order
        ]);
    }
}
