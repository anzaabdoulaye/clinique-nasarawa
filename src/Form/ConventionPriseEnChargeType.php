<?php

namespace App\Form;

use App\Entity\ConventionPriseEnCharge;
use App\Entity\OrganismePriseEnCharge;
use App\Enum\TypePrestationPEC;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConventionPriseEnChargeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organisme', EntityType::class, [
                'class' => OrganismePriseEnCharge::class,
                'choice_label' => 'nom',
                'label' => 'Organisme',
                'placeholder' => '— Sélectionner —',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('typePrestation', ChoiceType::class, [
                'label' => 'Type de prestation',
                'choices' => [
                    'Consultation' => TypePrestationPEC::CONSULTATION,
                    'Examen' => TypePrestationPEC::EXAMEN,
                    'Hospitalisation' => TypePrestationPEC::HOSPITALISATION,
                    'Acte' => TypePrestationPEC::ACTE,
                    'Soin' => TypePrestationPEC::SOIN,
                    'Consommable' => TypePrestationPEC::CONSOMMABLE,
                    'Autre' => TypePrestationPEC::AUTRE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tauxCouverture', IntegerType::class, [
                'label' => 'Taux (%)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('observation', TextareaType::class, [
                'label' => 'Observation',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConventionPriseEnCharge::class,
        ]);
    }
}