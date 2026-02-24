<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\Facture;
use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poids')
            ->add('taille')
            ->add('temperature')
            ->add('tensionArterielle')
            ->add('motifs')
            ->add('diagnostic')
            ->add('frequenceCardiaque')
            ->add('medecin', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => fn(Utilisateur $u) => $u->getNomComplet(),
            ])
            ->add('dossierMedical', EntityType::class, [
                'class' => DossierMedical::class,
                'choice_label' => 'id',
            ])
            ->add('rendezVous', EntityType::class, [
                'class' => RendezVous::class,
                'choice_label' => 'id',
            ])
            ->add('facture', EntityType::class, [
                'class' => Facture::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
