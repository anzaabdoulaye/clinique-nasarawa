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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ConsultationType extends AbstractType
{
     public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $context = $options['context']; // 'medical' | 'admin'

        // ===== Champs Phase C (toujours) =====
        $builder
            ->add('poids', NumberType::class, ['required' => false, 'label' => 'Poids (kg)'])
            ->add('taille', NumberType::class, ['required' => false, 'label' => 'Taille (cm)'])
            ->add('temperature', NumberType::class, ['required' => false, 'label' => 'Température (°C)'])
            ->add('tensionArterielle', null, ['required' => false, 'label' => 'Tension artérielle (ex: 120/80)'])
            ->add('frequenceCardiaque', NumberType::class, ['required' => false, 'label' => 'Fréquence cardiaque (bpm)'])

            ->add('motifs', TextareaType::class, ['required' => false, 'label' => 'Motif', 'attr' => ['rows' => 3]])
            ->add('histoire', TextareaType::class, ['required' => false, 'label' => 'Histoire / Anamnèse', 'attr' => ['rows' => 4]])
            ->add('examenClinique', TextareaType::class, ['required' => false, 'label' => 'Examen clinique', 'attr' => ['rows' => 4]])
            ->add('diagnostic', TextareaType::class, ['required' => false, 'label' => 'Diagnostic', 'attr' => ['rows' => 3]])
            ->add('conduiteATenir', TextareaType::class, ['required' => false, 'label' => 'Conduite à tenir', 'attr' => ['rows' => 4]])
        ;

        // CIM10 optionnel (si tu as l'entité)
        if ($builder->getData() && property_exists($builder->getData(), 'cim10')) {
            $builder->add('cim10', EntityType::class, [
                'class' => \App\Entity\Cim10Code::class,
                'required' => false,
                'placeholder' => '— Aucun code CIM10 —',
                'label' => 'Diagnostic CIM10 (optionnel)',
                'choice_label' => fn($c) => $c->getCode().' - '.$c->getLibelle(),
            ]);
        }

        // ===== Champs structurels (uniquement admin/full) =====
        if ($context === 'admin') {
            $builder
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
                    'required' => false,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
            'context' => 'medical', // par défaut : saisie médicale
        ]);

        $resolver->setAllowedValues('context', ['medical', 'admin']);
    }
}
