<?php

namespace App\Form;

use App\Entity\Medicament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('sku', TextType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->add('codeBarre', TextType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['class' => 'form-control', 'rows' => 3]])
            ->add('prixUnitaire', NumberType::class, ['scale' => 2, 'attr' => ['class' => 'form-control']])
            ->add('actif', CheckboxType::class, ['required' => false, 'attr' => ['class' => 'form-check-input']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medicament::class,
        ]);
    }
}
