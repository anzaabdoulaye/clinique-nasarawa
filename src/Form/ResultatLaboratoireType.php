<?php

namespace App\Form;

use App\Entity\ResultatLaboratoire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatLaboratoireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lignes', CollectionType::class, [
                'entry_type' => ResultatLaboratoireLigneType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
            ])
            ->add('conclusion', TextareaType::class, [
                'label' => 'Conclusion / Observation générale',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Conclusion biologique ou remarque générale...',
                ],
            ])
            ->add('antibiogrammes', CollectionType::class, [
                'entry_type' => ResultatAntibiogrammeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResultatLaboratoire::class,
        ]);
    }
}