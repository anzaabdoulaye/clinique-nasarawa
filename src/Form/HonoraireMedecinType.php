<?php

namespace App\Form;

use App\Entity\HonoraireMedecin;
use App\Entity\Medecin;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HonoraireMedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateActe', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de l\'acte'
            ])
            ->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                'choice_label' => 'nomComplet',
                'label' => 'Médecin traitant'
            ])
            ->add('nomPatient', TextType::class, [
                'label' => 'Nom du patient',
                'required' => false
            ])
            ->add('libelleActe', TextType::class, [
                'label' => 'Acte réalisé (ex: Echo Cardiaque)'
            ])
            ->add('montantTotal', NumberType::class, [
                'label' => 'Montant payé à la caisse (FCFA)'
            ])
            ->add('tauxReversement', NumberType::class, [
                'label' => 'Taux de reversement (ex: 0.40 pour 40%)',
                'scale' => 2
            ])
            // Nous avons retiré montantBrutMedecin, montantIsb et montantNetAPayer
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HonoraireMedecin::class,
        ]);
    }
}