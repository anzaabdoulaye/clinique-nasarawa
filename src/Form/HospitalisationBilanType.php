<?php

namespace App\Form;

use App\Entity\Hospitalisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HospitalisationBilanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hypothesesDiagnostiques', TextareaType::class, [
                'label' => 'Hypothèses diagnostiques',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Saisir les hypothèses...'],
            ])
            ->add('diagnosticPositif', TextareaType::class, [
                'label' => 'Diagnostic positif',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Diagnostic final retenu...'],
            ])
            // Mappage sur le JSON pour l'imagerie
            ->add('bilanImagerie', TextareaType::class, [
                'label' => 'Bilan Imagerie',
                'required' => false,
                'property_path' => 'bilanParaclinique[imagerie]',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Echographie, Scanner, IRM...'],
            ])
            // Mappage sur le JSON pour la biologie
            ->add('bilanBiologie', TextareaType::class, [
                'label' => 'Bilan Biologie / Biochimie',
                'required' => false,
                'property_path' => 'bilanParaclinique[biologie]',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'NFS, Ionogramme, Créatinine...'],
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