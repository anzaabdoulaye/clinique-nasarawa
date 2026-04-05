<?php

namespace App\Service;

use App\Entity\Hospitalisation;
use App\Enum\StatutHospitalisation;
use Doctrine\ORM\EntityManagerInterface;

class TreatmentAlertService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function getDashboardAlerts(): array
    {
        $today = new \DateTimeImmutable('today');
        $now = new \DateTimeImmutable();

        $missedTreatmentsCount = 0;
        $upcomingTreatmentsCount = 0;
        $endingTreatmentsCount = 0;
        $treatmentAlertItems = [];

        $hospitalisationsEnCours = $this->em->createQueryBuilder()
            ->select('h', 'd', 'p', 't', 'a')
            ->from(Hospitalisation::class, 'h')
            ->leftJoin('h.dossierMedical', 'd')
            ->leftJoin('d.patient', 'p')
            ->leftJoin('h.traitements', 't')
            ->leftJoin('t.administrations', 'a')
            ->where('h.statut = :encours')
            ->setParameter('encours', StatutHospitalisation::EN_COURS->value)
            ->getQuery()
            ->getResult();

        foreach ($hospitalisationsEnCours as $hospitalisation) {
            $patient = $hospitalisation->getDossierMedical()?->getPatient();

            foreach ($hospitalisation->getTraitements() as $traitement) {
                $dateDebut = $traitement->getDateDebut();
                $dateFin = $traitement->getDateFin();

                if (!$dateDebut || !$dateFin) {
                    continue;
                }

                $todayString = $today->format('Y-m-d');
                $dateDebutString = $dateDebut->format('Y-m-d');
                $dateFinString = $dateFin->format('Y-m-d');

                $isActiveToday = $dateDebutString <= $todayString && $dateFinString >= $todayString;

                if ($dateFinString === $todayString) {
                    $endingTreatmentsCount++;

                    $treatmentAlertItems[] = [
                        'type' => 'ending',
                        'label' => 'Fin de traitement aujourd’hui',
                        'patient' => $patient ? ($patient->getNom() . ' ' . $patient->getPrenom()) : 'Patient inconnu',
                        'traitement' => $traitement->getDescription(),
                        'heure' => null,
                        'hospitalisation_id' => $hospitalisation->getId(),
                    ];
                }

                if (!$isActiveToday) {
                    continue;
                }

                foreach ($traitement->getHeuresAdministration() as $heure) {
                    $heure = (int) $heure;
                    $alreadyAdministered = $traitement->isAdministeredAt($today, $heure);

                    if ($traitement->isLateSlotAt($today, $heure, $now) && !$alreadyAdministered) {
                        $missedTreatmentsCount++;

                        $treatmentAlertItems[] = [
                            'type' => 'missed',
                            'label' => 'Administration manquée',
                            'patient' => $patient ? ($patient->getNom() . ' ' . $patient->getPrenom()) : 'Patient inconnu',
                            'traitement' => $traitement->getDescription(),
                            'heure' => sprintf('%02dh', $heure),
                            'hospitalisation_id' => $hospitalisation->getId(),
                        ];
                    }

                    if (
                        !$alreadyAdministered &&
                        $traitement->isAdministrationWindowOpenAt($today, $heure, $now)
                    ) {
                        $upcomingTreatmentsCount++;

                        $treatmentAlertItems[] = [
                            'type' => 'upcoming',
                            'label' => 'Administration imminente',
                            'patient' => $patient ? ($patient->getNom() . ' ' . $patient->getPrenom()) : 'Patient inconnu',
                            'traitement' => $traitement->getDescription(),
                            'heure' => sprintf('%02dh', $heure),
                            'hospitalisation_id' => $hospitalisation->getId(),
                        ];
                    }
                }
            }
        }

        return [
            'missed_treatments_count' => $missedTreatmentsCount,
            'upcoming_treatments_count' => $upcomingTreatmentsCount,
            'ending_treatments_count' => $endingTreatmentsCount,
            'treatment_alert_items' => $treatmentAlertItems,
        ];
    }
}