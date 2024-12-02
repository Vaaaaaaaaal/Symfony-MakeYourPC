<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Type;

class ProductFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('search', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Rechercher un produit...',
                    'class' => 'form-control'
                ]
            ])
            ->add('price_min', NumberType::class, [
                'required' => false,
                'label' => 'Prix min',
                'attr' => [
                    'class' => 'input-int form-control'
                ]
            ])
            ->add('price_max', NumberType::class, [
                'required' => false,
                'label' => 'Prix max',
                'attr' => [
                    'class' => 'input-int form-control'
                ]
            ])
            ->add('type', EntityType::class, [
                'class' => Type::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tous',
                'label' => 'Type de produit',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('rating', ChoiceType::class, [
                'choices' => [
                    'Toutes les notes' => '',
                    '4 étoiles et plus' => '4',
                    '3 étoiles et plus' => '3',
                    '2 étoiles et plus' => '2',
                    '1 étoile et plus' => '1'
                ],
                'required' => false,
                'label' => 'Note minimale',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => null,
            'block_prefix' => '',
        ]);
    }
} 