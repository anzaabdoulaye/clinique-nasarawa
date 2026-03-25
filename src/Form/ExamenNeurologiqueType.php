<?php

namespace App\Form;

use App\Entity\ExamenNeurologique;
use App\Entity\Hospitalisation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenNeurologiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('conscience')
            ->add('tonusMusculaire')
            ->add('forceMembreSuperieurD')
            ->add('forceMembreSuperieurG')
            ->add('forceMembreInferieurD')
            ->add('forceMembreInferieurG')
            ->add('babinski')
            ->add('grasping')
            ->add('aphasieType')
            ->add('agnosie')
            ->add('apraxieType')
            ->add('troubleSphincteriens')
            ->add('raideurNuque')
            ->add('brudzinski')
            ->add('kernig')
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
            'data_class' => ExamenNeurologique::class,
        ]);
    }
}
