<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit'
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de produit',
                'choices' => [
                    'CPU' => 'CPU',
                    'GPU' => 'GPU',
                    'SSD' => 'SSD',
                    'Motherboard' => 'Motherboard',
                    // Ajoutez d'autres types si nécessaire
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock'
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
