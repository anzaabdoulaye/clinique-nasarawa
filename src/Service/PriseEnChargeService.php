<?php

namespace App\Service;

use App\Entity\Facture;
use App\Entity\FactureLigne;
use App\Entity\Patient;
use App\Entity\PatientCouverture;
use App\Enum\StatutFacture;
use App\Enum\TypePrestationPEC;
use App\Repository\ConventionPriseEnChargeRepository;

class PriseEnChargeService
{
    public function __construct(
        private readonly ConventionPriseEnChargeRepository $conventionRepository,
    ) {
    }

    public function recalculerFacture(Facture $facture, ?Patient $patient = null): void
    {
        $dateReference = new \DateTimeImmutable();

        $patient ??= $facture->getConsultation()?->getDossierMedical()?->getPatient();
        $couverture = $this->getCouvertureActive($patient, $dateReference);

        if ($facture->isPriseEnChargeActive() && $facture->isPriseEnChargeManuelle()) {
            // On garde l'organisme choisi sur la facture
            // Pas d'écrasement par la couverture patient
        } else {
            $facture->setOrganismePriseEnCharge($couverture?->getOrganisme());

            // si aucune couverture patient, on désactive la PEC automatique
            if (!$couverture) {
                $facture->setPriseEnChargeActive(false);
                $facture->setPriseEnChargeManuelle(false);
                $facture->setTauxPriseEnChargeManuel(null);
            }
        }

        $totalBrut = 0;
        $totalPriseEnCharge = 0;
        $totalPatient = 0;

        foreach ($facture->getLignes() as $ligne) {
            $this->recalculerLigne($ligne, $facture, $couverture, $dateReference);

            $totalBrut += $ligne->getMontantBrut();
            $totalPriseEnCharge += $ligne->getMontantPriseEnCharge();
            $totalPatient += $ligne->getMontantPatient();
        }

        $montantPayePatient = $this->calculerMontantPayePatient($facture);
        $restePatient = max(0, $totalPatient - $montantPayePatient);

        $facture->setMontantTotalBrut($totalBrut);
        $facture->setMontantTotalPriseEnCharge($totalPriseEnCharge);
        $facture->setMontantTotalPatient($totalPatient);
        $facture->setMontantPayePatient($montantPayePatient);
        $facture->setRestePatient($restePatient);

        // Compatibilité avec l'existant
        $facture->setMontantTotal($totalBrut);
        $facture->setMontantPaye($montantPayePatient);
        $facture->setResteAPayer($restePatient);

        $this->mettreAJourStatutFacture($facture);
    }

    private function recalculerLigne(
        FactureLigne $ligne,
        Facture $facture,
        ?PatientCouverture $couverture,
        \DateTimeInterface $dateReference
    ): void {
        $montantBrut = max(0, $ligne->getQuantite() * $ligne->getPrixUnitaire());

        $ligne->setTotal($montantBrut);
        $ligne->setMontantBrut($montantBrut);

        $type = $ligne->getTypePrestationPEC() ?? $this->infererTypeDepuisAncienChamp($ligne);
        $ligne->setTypePrestationPEC($type);

        $taux = $this->determinerTauxApplicable($facture, $couverture, $type, $dateReference);

        $montantPriseEnCharge = (int) round($montantBrut * $taux / 100);
        $montantPatient = $montantBrut - $montantPriseEnCharge;

        $ligne->setTauxPriseEnCharge($taux);
        $ligne->setMontantPriseEnCharge($montantPriseEnCharge);
        $ligne->setMontantPatient($montantPatient);
    }

    private function determinerTauxApplicable(
        Facture $facture,
        ?PatientCouverture $couverture,
        TypePrestationPEC $type,
        \DateTimeInterface $dateReference
    ): int {
        // 1. Priorité au mode manuel sur facture
        if ($facture->isPriseEnChargeActive() && $facture->isPriseEnChargeManuelle()) {
            $taux = (int) ($facture->getTauxPriseEnChargeManuel() ?? 0);

            // Règle métier : hospitalisation à 100 % minimum
            if ($type === TypePrestationPEC::HOSPITALISATION && $taux < 100) {
                return 100;
            }

            return max(0, min(100, $taux));
        }

        // 2. PEC via couverture patient / conventions
        if ($facture->isPriseEnChargeActive() && $couverture && $couverture->getOrganisme()) {
            $convention = $this->conventionRepository->findConventionActive(
                $couverture->getOrganisme(),
                $type,
                $dateReference
            );

            if ($convention) {
                return max(0, min(100, $convention->getTauxCouverture()));
            }
        }

        // 3. Pas de PEC
        return 0;
    }

    private function getCouvertureActive(?Patient $patient, \DateTimeInterface $date): ?PatientCouverture
    {
        if (!$patient) {
            return null;
        }

        if (!method_exists($patient, 'getCouverturePriseEnCharge')) {
            return null;
        }

        $couverture = $patient->getCouverturePriseEnCharge();

        if (!$couverture instanceof PatientCouverture) {
            return null;
        }

        if (!$couverture->estValideA($date)) {
            return null;
        }

        return $couverture;
    }

    private function calculerMontantPayePatient(Facture $facture): int
    {
        $total = 0;

        foreach ($facture->getPaiements() as $paiement) {
            $total += max(0, (int) $paiement->getMontant());
        }

        return $total;
    }

    private function infererTypeDepuisAncienChamp(FactureLigne $ligne): TypePrestationPEC
    {
        $type = mb_strtoupper((string) ($ligne->getType() ?? ''));

        return match ($type) {
            'CONSULTATION' => TypePrestationPEC::CONSULTATION,
            'EXAMEN', 'EXAMEN_BIOLOGIQUE', 'LABO', 'LABORATOIRE' => TypePrestationPEC::EXAMEN,
            'HOSPITALISATION' => TypePrestationPEC::HOSPITALISATION,
            'SOIN' => TypePrestationPEC::SOIN,
            'ACTE' => TypePrestationPEC::ACTE,
            'CONSOMMABLE' => TypePrestationPEC::CONSOMMABLE,
            default => TypePrestationPEC::AUTRE,
        };
    }

    private function mettreAJourStatutFacture(Facture $facture): void
    {
        $totalPatient = max(0, $facture->getMontantTotalPatient());
        $montantPayePatient = max(0, $facture->getMontantPayePatient());
        $restePatient = max(0, $facture->getRestePatient());

        // Cas 1 : tout est couvert par PEC
        if ($totalPatient === 0) {
            $facture->setStatut(StatutFacture::PAYE);

            if ($facture->getDatePaiement() === null) {
                $facture->setDatePaiement(new \DateTimeImmutable());
            }

            return;
        }

        // Cas 2 : rien payé par le patient
        if ($montantPayePatient <= 0) {
            $facture->setStatut(StatutFacture::NON_PAYE);
            $facture->setDatePaiement(null);
            return;
        }

        // Cas 3 : patient a soldé sa part
        if ($montantPayePatient >= $totalPatient || $restePatient <= 0) {
            $facture->setStatut(StatutFacture::PAYE);

            // on prend idéalement la date du dernier paiement
            $dernierPaiement = null;
            foreach ($facture->getPaiements() as $paiement) {
                if ($dernierPaiement === null || $paiement->getPayeLe() > $dernierPaiement->getPayeLe()) {
                    $dernierPaiement = $paiement;
                }
            }

            $facture->setDatePaiement(
                $dernierPaiement?->getPayeLe() ?? new \DateTimeImmutable()
            );

            $facture->setRestePatient(0);
            $facture->setResteAPayer(0);

            return;
        }

        // Cas 4 : paiement partiel réel
        $facture->setStatut(StatutFacture::PARTIELLEMENT_PAYE);
        $facture->setDatePaiement(null);
    }
}