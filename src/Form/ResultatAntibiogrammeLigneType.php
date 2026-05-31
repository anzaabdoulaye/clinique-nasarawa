<?php

namespace App\Form;

use App\Entity\Antibiotique;
use App\Entity\ResultatAntibiogrammeLigne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatAntibiogrammeLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('antibiotique', EntityType::class, [
                'class' => Antibiotique::class,
                'choice_label' => 'nom',
               // 'disabled' => true, // On le désactive car l'utilisateur ne doit pas le modifier, il sera généré
                'attr' => ['class' => 'form-select-sm fw-bold border-0 bg-transparent']
            ])
            ->add('sensibilite', ChoiceType::class, [
                'choices' => [
                    'S (Sensible)' => 'S',
                    'I (Intermédiaire)' => 'I',
                    'R (Résistant)' => 'R',
                ],
                'expanded' => true, // Transforme en boutons radio
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'attr' => ['class' => 'd-flex gap-3 align-items-center']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResultatAntibiogrammeLigne::class,
        ]);
    }
}