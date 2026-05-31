<?php

namespace App\Form;

use App\Entity\Germe;
use App\Entity\ResultatAntibiogramme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatAntibiogrammeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('germe', EntityType::class, [
                'class' => Germe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un germe identifié...',
                'attr' => ['class' => 'form-select js-germe-select']
            ])
            ->add('numeration', TextType::class, [
                'required' => false,
                'label' => 'Numération / Compte de Kass',
                'attr' => ['placeholder' => 'Ex: 10^5 UFC/ml', 'class' => 'form-control']
            ])
            ->add('commentaire', TextareaType::class, [
                'required' => false,
                'label' => 'Observation spécifique',
                'attr' => ['rows' => 2, 'class' => 'form-control']
            ])
            ->add('lignes', CollectionType::class, [
                'entry_type' => ResultatAntibiogrammeLigneType::class,
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
            'data_class' => ResultatAntibiogramme::class,
        ]);
    }
}