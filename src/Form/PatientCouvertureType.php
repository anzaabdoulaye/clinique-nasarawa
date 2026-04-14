<?php

namespace App\Form;

use App\Entity\OrganismePriseEnCharge;
use App\Entity\Patient;
use App\Entity\PatientCouverture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientCouvertureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => fn (Patient $p) => $p->getNomComplet() ?? trim(($p->getNom() ?? '') . ' ' . ($p->getPrenom() ?? '')),
                'label' => 'Patient',
                'placeholder' => '— Sélectionner —',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('organisme', EntityType::class, [
                'class' => OrganismePriseEnCharge::class,
                'choice_label' => 'nom',
                'label' => 'Organisme',
                'placeholder' => '— Sélectionner —',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('numeroAssure', TextType::class, [
                'label' => 'Numéro assuré',
                'required' => false,
                'attr' => ['class' => 'form-control'],
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
            'data_class' => PatientCouverture::class,
        ]);
    }
}