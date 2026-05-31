<?php

namespace App\Form;

use App\Entity\ResultatLaboratoireLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatLaboratoireLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('demande', TextareaType::class, [
                'label' => 'Demande',
                'required' => true,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Ex: CRP, NFS, Glycémie...',
                ],
            ])
            ->add('resultat', TextareaType::class, [
                'label' => 'Résultat',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Ex: 130 mg/l, TEST NEGATIF...',
                ],
            ])
            ->add('valeurNormale', TextareaType::class, [
                'label' => 'Valeurs normales',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Ex: < 6 mg/l, 0.60 - 1.10 g/l...',
                ],
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'required' => true,
                'attr' => [
                    'min' => 1,
                ],
            ])
             ->add('groupe', HiddenType::class, [
                'label' => 'groupe',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResultatLaboratoireLigne::class,
        ]);
    }
}