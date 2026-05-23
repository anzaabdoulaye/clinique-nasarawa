<?php

namespace App\Form;

use App\Entity\ExamenClinique;
use App\Entity\Hospitalisation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
            ->add('taille', IntegerType::class, [
    'label' => 'Taille',
    'attr' => [
        'placeholder' => 'ex: 180',
    ],
    'help' => 'Saisir la taille en centimètres (cm).'
])
->add('poids', NumberType::class, [
    'label' => 'Poids',
    'attr' => [
        'placeholder' => 'ex: 75.5',
    ],
    'help' => 'Saisir le poids en kilogrammes (kg).'
])
            ->add('imc')
            ->add('deshydratation')
            ->add('oedeme')
            ->add('notes')
        
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExamenClinique::class,
        ]);
    }
}
