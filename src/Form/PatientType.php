<?php

namespace App\Form;

use App\Entity\Patient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ===== Identité =====
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: ABDOULAYE',
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom(s)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Anza',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin'  => 'F',
                ],
                'placeholder' => '— Choisir —',
                'required' => true,
                'attr' => [
                    'class' => 'form-control' 
                ],
                
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: +227 90 00 00 00',
                    'inputmode' => 'tel',
                    'autocomplete' => 'tel',
                ],
                'help' => 'Format conseillé : +227 XX XX XX XX',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Quartier, rue, ville…',
                ],
            ])

            // ===== Contact d'urgence =====
            ->add('emergencyContactName', TextType::class, [
                'label' => 'Contact d’urgence - Nom',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Issa Mahamadou',
                ],
            ])
            ->add('emergencyContactRelation', TextType::class, [
                'label' => 'Relation',
                'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                    'placeholder' => 'Ex: Père / Mère / Frère / Conjoint…',
                ],
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => 'Téléphone du proche',
                'required' => false,
                'attr' => [
                    'class' => 'form-control' ,
                    'placeholder' => 'Ex: +227 90 00 00 00',
                    'inputmode' => 'tel',
                ],
            ])
            ->add('emergencyContactAddress', TextType::class, [
                'label' => 'Adresse du proche',
                'required' => false,
                'attr' => [
                    'class' => 'form-control ',
                    'placeholder' => 'Adresse du contact d’urgence',
                ],
            ])

            // ===== Infos médicales rapides =====
            ->add('groupeSanguin', ChoiceType::class, [
                'label' => 'Groupe sanguin',
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
                'placeholder' => '— Choisir —',
                'required' => false,
                'attr' => [
                        'class' => 'form-control' 
                    ],
                ])
            ->add('taille', TextType::class, [
                'label' => 'Taille',
                'required' => false,
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Ex: 1.72 (m)',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('poids', TextType::class, [
                'label' => 'Poids',
                'required' => false,
                'attr' => [
                    'class' => 'form-control ',
                    'placeholder' => 'Ex: 70 (kg)',
                    'inputmode' => 'decimal',
                ],
            ])

            // ===== Antécédents (Textarea: mieux que TextType) =====
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex: Pénicilline, arachide…',
                ],
            ])
            ->add('antecedentsMedicaux', TextareaType::class, [
                'label' => 'Antécédents médicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('antecedentsChirurgicaux', TextareaType::class, [
                'label' => 'Antécédents chirurgicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('maladiesChroniques', TextareaType::class, [
                'label' => 'Maladies chroniques',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('traitementEnCours', TextareaType::class, [
                'label' => 'Traitement en cours',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('handicap', TextareaType::class, [
                'label' => 'Handicap',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])

            // ===== Grossesse =====
            ->add('grossesse', ChoiceType::class, [
                'label' => 'Grossesse',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'placeholder' => '— Indifférent —',
                'required' => false,
                'attr' => [
                    'class' => 'form-control' 
                ],
            ])

            // ===== Code (affiché, non éditable) =====
            ->add('code', TextType::class, [
                'label' => 'Code patient',
                'required' => false,
                'disabled' => true,
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Auto-généré',
                ],
                'help' => 'Généré automatiquement à la création.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}