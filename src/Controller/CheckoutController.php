<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderShipping;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\CartRepository;
use Psr\Log\LoggerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CartRepository $cartRepository,
        LoggerInterface $logger
    ): Response {
        try {
            $user = $this->getUser();
            $cart = $cartRepository->findOneBy(['user' => $user]);

            if (!$cart || $cart->getItems()->isEmpty()) {
                return $this->redirectToRoute('app_cart');
            }

            $order = new Order();
            $order->setUser($user);
            
            $shipping = new OrderShipping();
            $shipping->setOrderRef($order);
            
            $shipping->setFirstName($user->getName());
            $shipping->setLastName($user->getSurname());
            $shipping->setEmail($user->getEmail());
            $shipping->setPhone($user->getTelephone() ?? '');
            $shipping->setAddress($user->getAdresse() ?? '');
            
            $form = $this->createForm(CheckoutType::class, $shipping);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->beginTransaction();
                try {
                    foreach ($cart->getItems() as $cartItem) {
                        $product = $cartItem->getProduct();
                        if ($product->getStock() < $cartItem->getQuantity()) {
                            throw new \Exception('Stock insuffisant pour ' . $product->getName());
                        }
                    }

                    $total = 0;
                    foreach ($cart->getItems() as $cartItem) {
                        $product = $cartItem->getProduct();
                        $quantity = $cartItem->getQuantity();
                        
                        $orderItem = new OrderItem();
                        $orderItem->setOrder($order)
                                 ->setProduct($product)
                                 ->setQuantity($quantity)
                                 ->setPrice($product->getPrice());

                        $newStock = $product->getStock() - $quantity;
                        $product->setStock($newStock);
                        
                        $entityManager->persist($orderItem);
                        $entityManager->persist($product);
                        
                        $total += $product->getPrice() * $quantity;
                    }

                    $order->setTotalAmount($total);
                    
                    $entityManager->persist($order);
                    $entityManager->persist($shipping);
                    $entityManager->remove($cart);
                    $entityManager->flush();
                    $entityManager->commit();

                    return $this->redirectToRoute('app_checkout_confirmation');
                    
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $logger->error('Erreur lors du traitement : ' . $e->getMessage());
                }
            }

            return $this->render('checkout/index.html.twig', [
                'form' => $form->createView(),
                'cart' => $cart
            ]);
            
        } catch (\Exception $e) {
            $logger->error('Erreur générale : ' . $e->getMessage());
            return $this->redirectToRoute('app_cart');
        }
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

    #[Route('/admin/order/{id}/edit', name: 'app_admin_order_edit')]
    public function editOrder(
        Order $order,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(OrderEditType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView()
        ]);
    }
}
