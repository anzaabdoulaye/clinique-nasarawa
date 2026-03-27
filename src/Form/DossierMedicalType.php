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
                'label' => 'Groupe sanguin',
                'required' => false,
                'placeholder' => '— Sélectionner —',
                'choices' => [
                    'A+' => 'A+',
                    'A-' => 'A-',
                    'B+' => 'B+',
                    'B-' => 'B-',
                    'AB+' => 'AB+',
                    'AB-' => 'AB-',
                    'O+' => 'O+',
                    'O-' => 'O-',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Décrire les allergies connues...',
                ],
            ])
            ->add('antecedentsMedicaux', TextareaType::class, [
                'label' => 'Antécédents médicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Antécédents médicaux du patient...',
                ],
            ])
            ->add('antecedentsChirurgicaux', TextareaType::class, [
                'label' => 'Antécédents chirurgicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Antécédents chirurgicaux du patient...',
                ],
            ])
            ->add('maladiesChroniques', TextareaType::class, [
                'label' => 'Maladies chroniques',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ex. diabète, HTA, asthme...',
                ],
            ])
            ->add('traitementEnCours', TextareaType::class, [
                'label' => 'Traitement en cours',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Traitement habituel ou en cours...',
                ],
            ])
            ->add('handicap', TextType::class, [
                'label' => 'Handicap',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Préciser si nécessaire...',
                ],
            ])
            ->add('grossesse', CheckboxType::class, [
                'label' => 'Grossesse en cours',
                'required' => false,
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations générales',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Observations générales du dossier médical...',
                ],
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