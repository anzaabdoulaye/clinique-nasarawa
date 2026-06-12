<?php

namespace App\Form;

use App\Entity\Lot;
use App\Entity\Medicament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AjustementStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lot', EntityType::class, [
                'class' => Lot::class,
                'choice_label' => function (Lot $lot) {
                    return sprintf('%s (Lot: %s) - Stock actuel: %d', 
                        $lot->getMedicament()->getNom(), 
                        $lot->getNumeroLot(), 
                        $lot->getQuantite()
                    );
                },
                'label' => 'Sélectionnez le lot à corriger',
                'attr' => ['class' => 'form-select select2-lot'] // Idéal pour Select2
            ])
            ->add('quantiteReelle', IntegerType::class, [
                'label' => 'Quantité physique (réellement comptée sur l\'étagère)',
                'attr' => [
                    'min' => 0,
                    'class' => 'form-control form-control-lg text-primary fw-bold'
                ]
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif obligatoire de la régularisation',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex: Erreur de saisie hier, boîte cassée, péremption...',
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas lié directement à une entité, on récupère un tableau de données
            'data_class' => null, 
        ]);
    }
}