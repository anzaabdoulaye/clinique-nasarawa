<?php

namespace App\Form;

use App\Entity\Lot;
use App\Entity\Medicament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('medicament', EntityType::class, [
                'class' => Medicament::class,
                'choice_label' => 'nom',
                
                'attr' => [
                    'class' => 'form-control select2-enable',
                    'placeholder' => '— Choisir un médicament —', 
                ],
            ])
            ->add('numeroLot', TextType::class, [
                'required' => false,
                'label' => 'Numéro de lot',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('datePeremption', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de péremption',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité initiale',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'help' => 'Cette quantité initiale générera automatiquement un bon d’entrée en comptabilité matière.',
            ])
            ->add('prixAchat', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'label' => 'Prix d’achat unitaire',
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lot::class,
        ]);
    }
}