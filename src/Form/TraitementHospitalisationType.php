<?php

namespace App\Form;

use App\Entity\TraitementHospitalisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TraitementHospitalisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $heures = [];
        for ($i = 0; $i < 24; $i++) {
            $heures[sprintf('%02dh', $i)] = $i;
        }

        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Médicament / Soin',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Détails du traitement...',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Début du traitement',
                'input' => 'datetime_immutable',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Fin du traitement',
                'input' => 'datetime_immutable',
            ])
            ->add('heuresAdministration', ChoiceType::class, [
                'choices' => $heures,
                'multiple' => true,
                'expanded' => true,
                'label' => 'Heures d\'administration',
                'choice_attr' => function () {
                    return ['class' => 'form-check-input'];
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TraitementHospitalisation::class,
        ]);
    }
}