<?php

namespace App\Form;

use App\Entity\ObservationMedicale;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObservationMedicaleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeObservation', ChoiceType::class, [
                'choices'  => [
                    // Diagnostics & Bilans
                    'Hypothèse Diagnostique' => 'Hypothèse Diagnostique',
                    'Diagnostic Positif' => 'Diagnostic Positif',
                    'Demande d\'examen (Imagerie/Biologie)' => 'Demande d\'examen',
                    'Résultat d\'examen' => 'Résultat d\'examen',
                    
                    // Suivi clinique
                    'Histoire de la maladie' => 'Histoire de la maladie',
                    'Note d\'évolution' => 'Note d\'évolution',
                    'Prescription' => 'Prescription',
                    'Compte-rendu opératoire' => 'Compte-rendu opératoire',
                    
                    // Antécédents
                    'Nouvel Antécédent Médical' => 'Antécédent Médical',
                    'Nouvel Antécédent Chirurgical' => 'Antécédent Chirurgical',
                    
                    'Autre' => 'Autre',
                ],
                'label' => 'Type de note / Acte',
                'attr' => [
                    'class' => 'form-select mb-3'
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Détails de l\'observation',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Saisissez vos notes médicales ici...',
                    'class' => 'form-control mb-3'
                ]
            ])
        ;
        // Remarque : On n'ajoute délibérément PAS 'dossier', 'medecinAuteur', ni 'createdAt'
        // pour empêcher toute manipulation via la requête HTTP.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ObservationMedicale::class,
        ]);
    }
}