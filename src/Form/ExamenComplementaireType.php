<?php

namespace App\Form;

use App\Entity\ExamenComplementaire;
use App\Entity\Hospitalisation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenComplementaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type')
            ->add('resultat')
            ->add('dateExamen', null, [
                'widget' => 'single_text',
            ])
            ->add('hospitalisation', EntityType::class, [
                'class' => Hospitalisation::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExamenComplementaire::class,
        ]);
    }
}
