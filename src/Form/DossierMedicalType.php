<?php

namespace App\Form;

use App\Entity\DossierMedical;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierMedicalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groupeSanguin', ChoiceType::class, [
                // ... (Garder vos options actuelles)
                'label' => 'Groupe sanguin',
                'required' => false,
                'placeholder' => '— Sélectionner —',
                'choices' => [
                    'A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-',
                    'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            
            // --- NOUVEAUX CHAMPS MAPPÉS SUR LE JSON ---
            ->add('antecedentsMedicaux', TextareaType::class, [
                'label' => 'Antécédents médicaux',
                'required' => false,
                'property_path' => 'antecedents[medicaux]',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Maladies antérieures...'],
            ])
            ->add('antecedentsChirurgicaux', TextareaType::class, [
                'label' => 'Antécédents chirurgicaux',
                'required' => false,
                'property_path' => 'antecedents[chirurgicaux]',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Opérations subies...'],
            ])
            ->add('antecedentsGyneco', TextareaType::class, [
                'label' => 'Antécédents Gynéco-obstétricaux',
                'required' => false,
                'property_path' => 'antecedents[gyneco_obstetricaux]',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Parité, gestité...'],
            ])
            ->add('modeDeVie', TextareaType::class, [
                'label' => 'Mode de vie',
                'required' => false,
                'property_path' => 'antecedents[mode_vie]',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Tabac, alcool, sédentarité...'],
            ])
            // ------------------------------------------

            ->add('maladiesChroniques', TextareaType::class, [
                'label' => 'Maladies chroniques',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('traitementEnCours', TextareaType::class, [
                'label' => 'Traitement en cours',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('handicap', TextType::class, [
                'label' => 'Handicap',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('grossesse', CheckboxType::class, [
                'label' => 'Grossesse en cours',
                'required' => false,
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations générales',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierMedical::class,
        ]);
    }
}