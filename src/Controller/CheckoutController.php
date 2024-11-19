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
                $this->addFlash('error', 'Votre panier est vide');
                return $this->redirectToRoute('app_cart');
            }

            $order = new Order();
            $order->setUser($user);
            
            $shipping = new OrderShipping();
            $shipping->setOrderRef($order);
            
            $shipping->setFirstName($user->getName() ?? '');
            $shipping->setLastName($user->getSurname() ?? '');
            $shipping->setEmail($user->getEmail() ?? '');
            $shipping->setPhone($user->getTelephone() ?? '');
            $shipping->setAddress($user->getAdresse() ?? '');
            
            $form = $this->createForm(CheckoutType::class, $shipping);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $logger->info('Formulaire soumis');
                
                if ($form->isValid()) {
                    $logger->info('Formulaire valide');
                    try {
                        $cardNumber = preg_replace('/\s+/', '', $form->get('cardNumber')->getData());
                        $cardExpiry = $form->get('cardExpiry')->getData();
                        $cardCvc = $form->get('cardCvc')->getData();

                        if (!preg_match('/^[0-9]{16}$/', $cardNumber) ||
                            !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $cardExpiry) ||
                            !preg_match('/^[0-9]{3,4}$/', $cardCvc)) {
                            throw new \Exception('Les informations de paiement sont invalides');
                        }

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
                        
                        $entityManager->beginTransaction();
                        try {
                            $entityManager->persist($order);
                            $entityManager->persist($shipping);
                            $entityManager->remove($cart);
                            $entityManager->flush();
                            $entityManager->commit();

                            $this->addFlash('success', 'Votre commande a été validée avec succès !');
                            return $this->redirectToRoute('app_checkout_confirmation');
                        } catch (\Exception $e) {
                            $entityManager->rollback();
                            $logger->error('Erreur lors de la persistance : ' . $e->getMessage());
                            throw $e;
                        }
                    } catch (\Exception $e) {
                        $logger->error('Erreur de validation : ' . $e->getMessage());
                        $this->addFlash('error', $e->getMessage());
                    }
                } else {
                    $logger->error('Formulaire invalide : ' . json_encode($form->getErrors(true)));
                    $this->addFlash('error', 'Le formulaire contient des erreurs');
                }
            }

            return $this->render('checkout/index.html.twig', [
                'form' => $form->createView(),
                'cart' => $cart
            ]);
        } catch (\Exception $e) {
            $logger->error('Erreur générale : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue');
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
            $this->addFlash('success', 'Commande mise à jour avec succès');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView()
        ]);
    }
}
