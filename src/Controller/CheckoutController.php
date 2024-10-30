<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\CartRepository;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CartRepository $cartRepository
    ): Response {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $order->setUser($user);
        $form = $this->createForm(CheckoutType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $total = 0;

            foreach ($cart->getItems() as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order)
                         ->setProduct($cartItem->getProduct())
                         ->setQuantity($cartItem->getQuantity())
                         ->setPrice($cartItem->getProduct()->getPrice());

                $entityManager->persist($orderItem);
                $total += $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
            }

            $order->setTotalAmount($total);
            $entityManager->persist($order);
            
            // Vider le panier
            $entityManager->remove($cart);
            $entityManager->flush();

            return $this->redirectToRoute('app_checkout_payment');
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/checkout/payment', name: 'app_checkout_payment')]
    public function payment(
        Request $request, 
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $orderId = $session->get('pending_order_id');
            $order = $entityManager->getRepository(Order::class)->find($orderId);

            if ($order) {
                $entityManager->persist($order);
                $entityManager->flush();

                $session->remove('cart');
                $session->remove('pending_order_id');

                $this->addFlash('success', 'Paiement effectué avec succès !');
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
}
