<?php

namespace App\Form;

use App\Entity\DossierMedical;
use App\Entity\ExamenClinique;
use App\Entity\ExamenNeurologique;
use App\Entity\Hospitalisation;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HospitalisationType extends AbstractType
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('dossierMedical', EntityType::class, [
    'label' => 'Dossier médical',
    'class' => DossierMedical::class,
    'placeholder' => '— Rechercher un dossier —',
    'required' => true,
    'choice_label' => function (DossierMedical $d) {
        $numero = $d->getNumeroDossier() ?: 'Dossier #' . $d->getId();

        if ($d->getPatient()) {
            return $numero . ' - ' . $d->getPatient()->getNom() . ' ' . $d->getPatient()->getPrenom();
        }

        return $numero;
    },
    'attr' => [
        'class' => 'form-select select2-enable',
        'data-placeholder' => 'Rechercher dossier + patient...',
    ],
])

            ->add('medecinReferent', EntityType::class, [
                'label' => 'Médecin référent',
                'class' => Utilisateur::class,
                'choices' => $this->utilisateurRepository->findDoctors(),
                'placeholder' => '— Sélectionner —',
                'required' => true,
                'choice_label' => function (Utilisateur $u) {
                    if (method_exists($u, 'getNomComplet') && $u->getNomComplet()) {
                        return $u->getNomComplet();
                    }
                    $nom = method_exists($u, 'getNom') ? ($u->getNom() ?? '') : '';
                    $prenom = method_exists($u, 'getPrenom') ? ($u->getPrenom() ?? '') : '';
                    $full = trim($nom . ' ' . $prenom);
                    return $full !== '' ? $full : ('Utilisateur #' . $u->getId());
                },
                'attr' => ['class' => 'form-select'],
            ])

            ->add('dateAdmission', DateTimeType::class, [
                'label' => 'Date d’admission',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('statut', null, [
                'label' => 'Statut',
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])

            ->add('dateSortie', DateTimeType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'required' => false,
                'help' => 'Renseigner uniquement si le patient est sorti.',
                'attr' => ['class' => 'form-control'],
            ])

            ->add('motifAdmission', TextareaType::class, [
                'label' => 'Motif d’admission',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Ex: Douleurs abdominales, fièvre…'],
            ])

            ->add('histoireMaladie', TextareaType::class, [
                'label' => 'Histoire de la maladie',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])

            ->add('evolution', TextareaType::class, [
                'label' => 'Évolution',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])

            ->add('conclusion', TextareaType::class, [
                'label' => 'Conclusion',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])

            ->add('examenClinique', EntityType::class, [
                'label' => 'Examen clinique',
                'class' => ExamenClinique::class,
                'placeholder' => '— (optionnel) —',
                'required' => false,
                'choice_label' => fn (ExamenClinique $e) => 'Examen clinique #' . $e->getId(),
                'attr' => ['class' => 'form-select'],
            ])

            ->add('examenNeurologique', EntityType::class, [
                'label' => 'Examen neurologique',
                'class' => ExamenNeurologique::class,
                'placeholder' => '— (optionnel) —',
                'required' => false,
                'choice_label' => fn (ExamenNeurologique $e) => 'Examen neuro #' . $e->getId(),
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hospitalisation::class,
        ]);
    }
}