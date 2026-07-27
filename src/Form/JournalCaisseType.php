<?php

namespace App\Form;

use App\Entity\JournalCaisse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalCaisseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateOperation')
            ->add('libelle')
            ->add('montant')
            ->add('typeOperation')
            ->add('categorie')
            ->add('pieceJustificative')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('modifiedAt', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JournalCaisse::class,
        ]);
    }
}
