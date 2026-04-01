<?php

namespace App\Form;

use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateHeure', DateTimeType::class, [
                'label' => 'Date & heure',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // ✅ Si statut est un enum, tu peux passer à EnumType (voir note plus bas)
            ->add('statut', null, [
                'label' => 'Statut',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('motif', TextareaType::class, [
                'label' => 'Motif',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ex: Contrôle, douleur, renouvellement ordonnance…',
                ],
            ])

            ->add('patient', EntityType::class, [
                'label' => 'Patient',
                'class' => Patient::class,
                'placeholder' => '— Sélectionner un patient —',
                'required' => true,
                'choice_label' => function (Patient $p) {
                    $nom = $p->getNom() ?? '';
                    $prenom = $p->getPrenom() ?? '';
                    $code = $p->getCode() ?? '';
                    $full = trim($nom . ' ' . $prenom);
                    $label = $full !== '' ? $full : 'Patient';
                    return $code !== '' ? $label . ' — ' . $code : $label . ' (#' . $p->getId() . ')';
                },
                'attr' => [
                    'class' => 'form-select js-select2-patient',
                    'data-placeholder' => 'Rechercher un patient...',
                ],
            ])

            ->add('medecin', EntityType::class, [
                'label' => 'Médecin',
                'class' => Utilisateur::class,
                'placeholder' => '— (optionnel) —',
                'required' => false,
                'choice_label' => function (Utilisateur $u) {
                    if (method_exists($u, 'getNomComplet') && $u->getNomComplet()) {
                        return $u->getNomComplet();
                    }
                    $nom = method_exists($u, 'getNom') ? ($u->getNom() ?? '') : '';
                    $prenom = method_exists($u, 'getPrenom') ? ($u->getPrenom() ?? '') : '';
                    $full = trim($nom . ' ' . $prenom);
                    return $full !== '' ? $full : ('Utilisateur #' . $u->getId());
                },
                'attr' => [
                    'class' => 'form-select js-select2-medecin',
                    'data-placeholder' => 'Rechercher un médecin...',
                ],
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