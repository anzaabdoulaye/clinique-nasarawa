<?php

namespace App\Form;

use App\Entity\ExamenClinique;
use App\Entity\Hospitalisation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenCliniqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tensionArterielle')
            ->add('pouls')
            ->add('temperature')
            ->add('saturationOxygene')
            ->add('frequenceRespiratoire')
            ->add('poids')
            ->add('taille')
            ->add('imc')
            ->add('deshydratation')
            ->add('oedeme')
            ->add('notes')
            ->add('hospitalisation', EntityType::class, [
                'class' => Hospitalisation::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExamenClinique::class,
        ]);
    }
}
