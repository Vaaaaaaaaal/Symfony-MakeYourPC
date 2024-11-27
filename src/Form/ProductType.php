<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'placeholder' => 'Entrez le nom du produit'
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Entrez le prix',
                    'step' => '0.01',
                    'min' => '0'
                ]
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock disponible',
                'attr' => [
                    'placeholder' => 'Entrez la quantité en stock'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('type', EntityType::class, [
                'class' => Type::class,
                'choice_label' => 'name',
                'label' => 'Type de produit',
                'placeholder' => 'Sélectionnez un type',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
