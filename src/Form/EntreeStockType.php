<?php

namespace App\Form;

use App\Entity\Medicament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntreeStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('medicament', EntityType::class, [
                'class' => Medicament::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un médicament ou consommable...',
                'label' => 'Produit réceptionné',
                'attr' => ['class' => 'form-select shadow-sm']
            ])
            ->add('numeroLot', TextType::class, [
                'label' => 'Numéro de Lot',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: L-2026-05A', 
                    'class' => 'form-control shadow-sm'
                ]
            ])
            ->add('datePeremption', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de péremption',
                'required' => true,
                'attr' => ['class' => 'form-control shadow-sm']
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité reçue',
                'attr' => [
                    'min' => 1, 
                    'class' => 'form-control shadow-sm form-control-lg text-success fw-bold'
                ]
            ])
            ->add('prixAchat', NumberType::class, [
                'label' => 'Prix d\'achat unitaire (FCFA)',
                'html5' => true,
                'required' => true,
                'attr' => [
                    'step' => 'any', 
                    'min' => 0, 
                    'class' => 'form-control shadow-sm',
                    'placeholder' => 'Coût unitaire pour la clinique'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Ce formulaire n'est pas lié directement à une entité car il alimente Lot ET MouvementStock
            'data_class' => null, 
        ]);
    }
}