<?php

namespace App\Form;
// src/Form/TraitementHospitalisationType.php

use App\Entity\TraitementHospitalisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class TraitementHospitalisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Médicament / Soin',
                'attr' => ['rows' => 3, 'placeholder' => 'Détails du traitement...'],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Début du traitement',
                'input' => 'datetime_immutable',
                'html5' => true,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Fin du traitement',
                'input' => 'datetime_immutable',
                'html5' => true,
            ])
            ->add('planification', HiddenType::class, [
                'label' => false,
            ]);

        // Transformer le tableau en JSON pour le champ hidden
        $builder->get('planification')
            ->addModelTransformer(new CallbackTransformer(
                fn($array) => $array ? json_encode($array) : '{}',
                fn($json) => is_string($json) && !empty($json) ? json_decode($json, true) : []
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TraitementHospitalisation::class,
        ]);
    }
}