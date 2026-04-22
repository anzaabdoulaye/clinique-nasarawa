<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Cim10Code;
use App\Entity\DossierMedical;
use App\Entity\Facture;
use App\Entity\RendezVous;
use App\Entity\TarifPrestation;
use App\Entity\Utilisateur;
use App\Enum\StatutRendezVous;
use App\Repository\DossierMedicalRepository;
use App\Repository\RendezVousRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConsultationType extends AbstractType
{
    public function __construct(
    private UtilisateurRepository $utilisateurRepository,
    private DossierMedicalRepository $dossierMedicalRepository,
    private RendezVousRepository $rendezVousRepository,
    private UrlGeneratorInterface $urlGenerator,
) {
}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $context = $options['context'];

        if ($context === 'medical') {
            $builder
                ->add('poids', NumberType::class, [
                    'required' => false,
                    'label' => 'Poids (kg)',
                    'scale' => 2,
                ])
                ->add('taille', NumberType::class, [
                    'required' => false,
                    'label' => 'Taille (cm)',
                    'scale' => 2,
                ])
                ->add('temperature', NumberType::class, [
                    'required' => false,
                    'label' => 'Température (°C)',
                    'scale' => 2,
                ])
                ->add('tensionArterielle', null, [
                    'required' => false,
                    'label' => 'Tension artérielle (ex: 120/80)',
                ])
                ->add('frequenceCardiaque', NumberType::class, [
                    'required' => false,
                    'label' => 'Fréquence cardiaque (bpm)',
                    'scale' => 0,
                ])
                ->add('frequenceRespiratoire', NumberType::class, [
                    'required' => false,
                    'label' => 'Fréquence respiratoire (cycles/min)',
                    'scale' => 0,
                ])
                ->add('motifs', TextareaType::class, [
                    'required' => false,
                    'label' => 'Motif',
                    'attr' => ['rows' => 3],
                ])
                ->add('histoire', TextareaType::class, [
                    'required' => false,
                    'label' => 'Histoire / Anamnèse',
                    'attr' => ['rows' => 4],
                ])
                ->add('examenClinique', TextareaType::class, [
                    'required' => false,
                    'label' => 'Examen clinique',
                    'attr' => ['rows' => 4],
                ])
                ->add('diagnostic', TextareaType::class, [
                    'required' => false,
                    'label' => 'Diagnostic',
                    'attr' => ['rows' => 3],
                ])
                ->add('conduiteATenir', TextareaType::class, [
                    'required' => false,
                    'label' => 'Conduite à tenir',
                    'attr' => ['rows' => 4],
                ]);

            if ($builder->getData() && property_exists($builder->getData(), 'cim10')) {
                $builder->add('cim10', EntityType::class, [
                    'class' => Cim10Code::class,
                    'required' => false,
                    'placeholder' => '— Aucun code CIM10 —',
                    'label' => 'Diagnostic CIM10 (optionnel)',
                    'choice_label' => fn (Cim10Code $c) => $c->getCode() . ' - ' . $c->getLibelle(),
                ]);
            }
        }

        if ($context === 'admin') {
            $currentRendezVous = $builder->getData()?->getRendezVous();
            $currentDossierMedical = $builder->getData()?->getDossierMedical();
            $doctorChoices = $this->utilisateurRepository->findDoctors();


/** @var Utilisateur|null $currentUser */
    $currentUser = $options['current_user'] ?? null;

    $isMedecinConnecte = $currentUser instanceof Utilisateur
        && in_array('ROLE_MEDECIN', $currentUser->getRoles(), true);

    $doctorChoices = array_values(array_filter(
        $this->utilisateurRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']),
        static fn (Utilisateur $utilisateur) => in_array('ROLE_MEDECIN', $utilisateur->getRoles(), true)
    ));

    $currentMedecin = $builder->getData()?->getMedecin();

    $builder->add('medecin', EntityType::class, [
        'class' => Utilisateur::class,
        'choices' => $doctorChoices,
        'choice_label' => fn (Utilisateur $u) => $u->getNomComplet(),
        'placeholder' => '— Choisir un médecin —',
        'label' => 'Médecin',
        'required' => true,
        'disabled' => $isMedecinConnecte,
        'data' => $isMedecinConnecte ? $currentUser : $currentMedecin,
        'attr' => [
            'class' => 'js-select2-medecin',
            'data-placeholder' => 'Rechercher un médecin...',
        ],
    ]);

    $builder
        ->add('dossierMedical', EntityType::class, [
            'class' => DossierMedical::class,
            'query_builder' => static function (DossierMedicalRepository $repository) {
                return $repository->createQueryBuilder('d')
                    ->leftJoin('d.patient', 'p')
                    ->addSelect('p')
                    ->orderBy('d.createdAt', 'DESC')
                    ->addOrderBy('d.id', 'DESC');
            },
            'choice_label' => function (DossierMedical $dossier) {
                $patient = method_exists($dossier, 'getPatient') ? $dossier->getPatient() : null;
                $patientLabel = $patient
                    ? trim(($patient->getCode() ?? '') . ' - ' . ($patient->getNom() ?? '') . ' ' . ($patient->getPrenom() ?? '') . ' - ' . ($patient->getTelephone() ?? ''))
                    : 'Sans patient';

                return ($dossier->getNumeroDossier() ?: ('#' . $dossier->getId())) . ' / ' . $patientLabel;
            },
            'placeholder' => '— Choisir un dossier médical —',
            'label' => 'Dossier médical',
            'required' => true,
            'attr' => [
                'class' => 'js-select2-dossier',
                'data-placeholder' => 'Rechercher un dossier médical...',
            ],
        ])
        ->add('motifs', TextareaType::class, [
            'required' => false,
            'label' => 'Motif de consultation',
            'attr' => ['rows' => 3],
        ]);

            $addRendezVousField = function ($form, ?DossierMedical $dossierMedical, ?RendezVous $selectedRendezVous = null): void {
                $form->add('rendezVous', EntityType::class, [
                    'class' => RendezVous::class,
                    'choices' => $this->rendezVousRepository->findSelectableForDossierMedical(
                        $dossierMedical,
                        $selectedRendezVous,
                    ),
                    'choice_label' => function (RendezVous $rdv) {
                        $patient = $rdv->getPatient();
                        $date = method_exists($rdv, 'getDateHeure') && $rdv->getDateHeure()
                            ? $rdv->getDateHeure()->format('d/m/Y H:i')
                            : 'Sans date';
                        $patientLabel = trim(sprintf(
                            '%s %s',
                            $patient->getNom() ?? '',
                            $patient->getPrenom() ?? ''
                        ));

                        if ($patient->getCode()) {
                            $patientLabel = trim($patient->getCode() . ' - ' . $patientLabel, ' -');
                        }

                        return 'RDV #' . $rdv->getId() . ' - ' . $date . ($patientLabel !== '' ? ' - ' . $patientLabel : '');
                    },
                    'required' => false,
                    'placeholder' => '— Aucun rendez-vous —',
                    'label' => 'Rendez-vous',
                    'attr' => [
                        'class' => 'js-select2-rendezvous js-rendezvous-by-dossier',
                        'data-placeholder' => 'Rechercher un rendez-vous...',
                        'data-rendezvous-url' => $this->urlGenerator->generate('app_consultation_rendezvous_options'),
                        'data-current-rendezvous-id' => $selectedRendezVous?->getId(),
                    ],
                ]);
            };

            $addRendezVousField($builder, $currentDossierMedical, $currentRendezVous);

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($addRendezVousField): void {
                $consultation = $event->getData();
                $form = $event->getForm();

                if (!$consultation instanceof Consultation) {
                    $addRendezVousField($form, null, null);
                    return;
                }

                $addRendezVousField(
                    $form,
                    $consultation->getDossierMedical(),
                    $consultation->getRendezVous(),
                );
            });

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($addRendezVousField): void {
                $data = $event->getData();
                $form = $event->getForm();

                if (!is_array($data)) {
                    $addRendezVousField($form, null, null);
                    return;
                }

                $dossierMedical = null;
                $selectedRendezVous = null;

                $dossierMedicalId = isset($data['dossierMedical']) ? (int) $data['dossierMedical'] : 0;
                $selectedRendezVousId = isset($data['rendezVous']) ? (int) $data['rendezVous'] : 0;

                if ($dossierMedicalId > 0) {
                    $dossierMedical = $this->dossierMedicalRepository->find($dossierMedicalId);
                }

                if ($selectedRendezVousId > 0) {
                    $selectedRendezVous = $this->rendezVousRepository->find($selectedRendezVousId);
                }

                $addRendezVousField($form, $dossierMedical, $selectedRendezVous);
            });

            if ($builder->getData() && property_exists($builder->getData(), 'dateConsultation')) {
                $builder->add('dateConsultation', DateTimeType::class, [
                    'required' => false,
                    'label' => 'Date de consultation',
                    'widget' => 'single_text',
                ]);
            }

            if ($builder->getData() && property_exists($builder->getData(), 'tarifPrestation')) {
                $builder->add('tarifPrestation', EntityType::class, [
                    'class' => TarifPrestation::class,
                    'choice_label' => 'libelle',
                    'required' => false,
                    'placeholder' => 'Choisir un acte, examen ou consommable',
                    'label' => 'Tarif / prestation',
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
            'context' => 'medical',
            'current_user' => null,
        ]);

        $resolver->setAllowedValues('context', ['medical', 'admin']);
    }
}