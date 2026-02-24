<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
   public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateHeure', null, [
                'widget' => 'single_text',
            ])
            ->add('statut')
            ->add('motif', null, [
                'required' => false,
            ])
            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => fn(Patient $p) => sprintf('%s %s (#%d)', $p->getNom(), $p->getPrenom(), $p->getId()),
            ])
            ->add('medecin', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
                'required' => false, // si tu veux permettre RDV sans médecin
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}
