<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\OrderShipping;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('cardNumber', TextType::class, [
                'label' => 'Numéro de carte',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[0-9\s]{19}$/',
                        'message' => 'Le numéro de carte doit contenir 16 chiffres'
                    ])
                ],
                'attr' => ['placeholder' => '1234 5678 9012 3456']
            ])
            ->add('cardExpiry', TextType::class, [
                'label' => 'Date d\'expiration (MM/YY)',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
                        'message' => 'Format invalide (MM/YY)'
                    ])
                ],
                'attr' => ['placeholder' => 'MM/YY']
            ])
            ->add('cardCvc', TextType::class, [
                'label' => 'CVC',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{3,4}$/',
                        'message' => 'Le CVC doit contenir 3 ou 4 chiffres'
                    ])
                ],
                'attr' => ['placeholder' => '123']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderShipping::class,
        ]);
    }
} 