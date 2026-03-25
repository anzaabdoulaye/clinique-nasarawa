<?php

namespace App\Form;

use App\Entity\DossierMedical;
use App\Entity\ExamenClinique;
use App\Entity\ExamenNeurologique;
use App\Entity\Hospitalisation;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HospitalisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateAdmission', null, [
                'widget' => 'single_text',
            ])
            ->add('dateSortie', null, [
                'widget' => 'single_text',
            ])
            ->add('motifAdmission')
            ->add('histoireMaladie')
            ->add('evolution')
            ->add('conclusion')
            ->add('statut')
            
            ->add('dossierMedical', EntityType::class, [
                'class' => DossierMedical::class,
                'choice_label' => 'id',
            ])
            ->add('medecinReferent', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('examenClinique', EntityType::class, [
                'class' => ExamenClinique::class,
                'choice_label' => 'id',
            ])
            ->add('examenNeurologique', EntityType::class, [
                'class' => ExamenNeurologique::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hospitalisation::class,
        ]);
    }
}
