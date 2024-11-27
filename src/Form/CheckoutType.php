<?php

namespace App\Form;

use App\Entity\OrderShipping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\s]{2,30}$/',
                        'message' => 'Le prénom doit contenir entre 2 et 30 caractères'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\s]{2,30}$/',
                        'message' => 'Le nom doit contenir entre 2 et 30 caractères'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'L\'email n\'est pas valide'])
                ]
            ])
            ->add('phone', TelType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/',
                        'message' => 'Le numéro de téléphone n\'est pas valide'
                    ])
                ]
            ])
            ->add('address', TextType::class, [
                'constraints' => [new Assert\NotBlank(['message' => 'L\'adresse est obligatoire'])]
            ])
            ->add('city', TextType::class, [
                'constraints' => [new Assert\NotBlank(['message' => 'La ville est obligatoire'])]
            ])
            ->add('postalCode', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code postal est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit contenir 5 chiffres'
                    ])
                ]
            ])
            ->add('cardNumber', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de carte est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9\s]{19}$/',
                        'message' => 'Le numéro de carte doit contenir 16 chiffres'
                    ])
                ]
            ])
            ->add('cardExpiry', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'expiration est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
                        'message' => 'Format invalide (MM/YY)'
                    ])
                ]
            ])
            ->add('cardCvc', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le CVC est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{3,4}$/',
                        'message' => 'Le CVC doit contenir 3 ou 4 chiffres'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderShipping::class,
        ]);
    }
} 