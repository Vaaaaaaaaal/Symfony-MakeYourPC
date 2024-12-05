<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\CartManager;
use App\Service\OrderManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CheckoutController extends AbstractController
{
    public function __construct(
        private OrderManager $orderManager,
        private CartManager $cartManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/checkout', name: 'app_checkout')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $session = $request->getSession();
        if ($session->get('checkout_processing', false)) {
            return $this->redirectToRoute('app_cart');
        }
        
        try {
            $user = $this->getUser();
            $cart = $this->cartManager->getOrCreateCart($user);

            if (!$cart || $cart->getItems()->isEmpty()) {
                return $this->redirectToRoute('app_cart');
            }

            $shipping = $this->orderManager->createOrderShipping(new Order(), $user);
            
            $form = $this->createForm(CheckoutType::class, $shipping, [
                'user' => $user
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $selectedAddress = $form->get('savedAddress')->getData();
                    if ($selectedAddress) {
                        $this->orderManager->updateShippingFromAddress($shipping, $selectedAddress);
                    }

                    $order = $this->orderManager->createOrderFromCart($cart, $user);
                    $shipping->setOrderRef($order);
                    
                    $entityManager->persist($shipping);
                    $this->orderManager->finalizeOrder($order, $cart);
                    
                    $this->addFlash('success', 'Votre commande a été traitée avec succès');
                    return $this->redirectToRoute('app_home');
                    
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du traitement : ' . $e->getMessage());
                }
            }

            $session->remove('checkout_processing');
            
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
    public function payment(Request $request, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $orderId = $session->get('pending_order_id');
            $order = $this->orderManager->getOrder($orderId);

            if ($order) {
                $cart = $this->cartManager->getOrCreateCart($this->getUser());
                $this->orderManager->finalizeOrder($order, $cart);
                $session->remove('cart');
                $session->remove('pending_order_id');

                return $this->redirectToRoute('app_order_confirmation');
            }
        }

        return $this->render('checkout/payment.html.twig');
    }

    #[Route('/checkout/confirmation', name: 'app_checkout_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('checkout/confirmation.html.twig');
    }

    #[Route('/admin/order/{id}/view', name: 'app_admin_order_view')]
    #[IsGranted('ROLE_ADMIN')]
    public function viewOrder(Order $order): Response
    {
        return $this->render('admin/order/view.html.twig', [
            'order' => $order
        ]);
    }
}
