<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Entity\FactureLigne;
use App\Entity\Paiement;
use App\Entity\PrescriptionPrestation;
use App\Enum\ModePaiement;
use App\Enum\StatutFacture;
use App\Enum\StatutPrescriptionPrestation;
use App\Repository\FactureLigneRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;

class FacturationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FactureRepository $factureRepository,
        private FactureLigneRepository $factureLigneRepository,
    ) {
    }

    public function initialiserOuRecupererFacture(Consultation $consultation): Facture
    {
        $facture = $consultation->getFacture();

        if ($facture instanceof Facture) {
            return $facture;
        }

        $facture = new Facture();
        $facture->setConsultation($consultation);
        $facture->setStatut(StatutFacture::BROUILLON);
        $facture->setDateEmission(new \DateTimeImmutable());

        $consultation->setFacture($facture);

        $this->em->persist($facture);

        return $facture;
    }

    public function synchroniserDepuisPrescription(PrescriptionPrestation $prescription): Facture
    {
        $consultation = $prescription->getConsultation();
        if (!$consultation instanceof Consultation) {
            throw new \LogicException('La prescription prestation doit être liée à une consultation.');
        }

        $facture = $this->initialiserOuRecupererFacture($consultation);

        if (!$prescription->isAFacturer()) {
            $this->recalculerFacture($facture);
            return $facture;
        }

        $ligne = $this->trouverLigneParPrescription($prescription);

        if (!$ligne) {
            $ligne = new FactureLigne();
            $ligne->setFacture($facture);
            $ligne->setPrescriptionPrestation($prescription);

            $facture->addLigne($ligne);
            $this->em->persist($ligne);
        }

        $ligne->setLibelle($prescription->getLibelle() ?? 'Prestation');
        $ligne->setQuantite($prescription->getQuantite());
        $ligne->setPrixUnitaire($prescription->getPrixReference());
        $ligne->setType($prescription->getCategorieLabel());

        if ($prescription->getStatut() === StatutPrescriptionPrestation::PRESCRIT) {
            $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
        }

        $this->recalculerFacture($facture);

        return $facture;
    }

    public function synchroniserFactureDepuisConsultation(Consultation $consultation): Facture
    {
        $facture = $this->initialiserOuRecupererFacture($consultation);

        foreach ($consultation->getPrescriptionsPrestations() as $prescription) {
            if (!$prescription instanceof PrescriptionPrestation) {
                continue;
            }

            if (!$prescription->isAFacturer()) {
                continue;
            }

            $ligne = $this->trouverLigneParPrescription($prescription);

            if (!$ligne) {
                $ligne = new FactureLigne();
                $ligne->setFacture($facture);
                $ligne->setPrescriptionPrestation($prescription);

                $facture->addLigne($ligne);
                $this->em->persist($ligne);
            }

            $ligne->setLibelle($prescription->getLibelle() ?? 'Prestation');
            $ligne->setQuantite($prescription->getQuantite());
            $ligne->setPrixUnitaire($prescription->getPrixReference());
            $ligne->setType($prescription->getCategorieLabel());

            if ($prescription->getStatut() === StatutPrescriptionPrestation::PRESCRIT) {
                $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
            }
        }

        $this->supprimerLignesOrphelines($facture, $consultation);
        $this->recalculerFacture($facture);

        return $facture;
    }

    public function ajouterPaiement(Facture $facture, int $montant, ModePaiement $mode): Paiement
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être supérieur à zéro.');
        }

        $paiement = new Paiement();
        $paiement->setFacture($facture);
        $paiement->setMontant($montant);
        $paiement->setMode($mode);
        $paiement->setPayeLe(new \DateTimeImmutable());

        $facture->addPaiement($paiement);

        $this->em->persist($paiement);

        $this->recalculerFacture($facture);
        $this->mettreAJourStatutPrestations($facture);

        return $paiement;
    }

    public function recalculerFacture(Facture $facture): void
    {
        foreach ($facture->getLignes() as $ligne) {
            $ligne->setTotal($ligne->getQuantite() * $ligne->getPrixUnitaire());
        }

        $facture->recalculerMontants();
    }

    public function mettreAJourStatutPrestations(Facture $facture): void
    {
        $statutFacture = $facture->getStatut();

        foreach ($facture->getLignes() as $ligne) {
            $prescription = $ligne->getPrescriptionPrestation();

            if (!$prescription instanceof PrescriptionPrestation) {
                continue;
            }

            if ($statutFacture === StatutFacture::PAYE) {
                $prescription->setStatut(StatutPrescriptionPrestation::PAYE);
                continue;
            }

            if ($statutFacture === StatutFacture::PARTIELLEMENT_PAYE) {
                if ($prescription->getStatut() === StatutPrescriptionPrestation::FACTURE) {
                    $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
                }
                continue;
            }

            if (\in_array($statutFacture, [StatutFacture::BROUILLON, StatutFacture::NON_PAYE], true)) {
                if ($prescription->getStatut() === StatutPrescriptionPrestation::PAYE) {
                    $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
                }
            }

            if ($statutFacture === StatutFacture::ANNULE) {
                $prescription->setStatut(StatutPrescriptionPrestation::ANNULE);
            }
        }
    }

    public function annulerFacture(Facture $facture): void
    {
        $facture->setStatut(StatutFacture::ANNULE);
        $facture->setDatePaiement(null);
        $facture->setMontantPaye(0);
        $facture->setResteAPayer($facture->getMontantTotal());

        $this->mettreAJourStatutPrestations($facture);
    }

    private function trouverLigneParPrescription(PrescriptionPrestation $prescription): ?FactureLigne
    {
        return $this->factureLigneRepository->findOneBy([
            'prescriptionPrestation' => $prescription,
        ]);
    }

    private function supprimerLignesOrphelines(Facture $facture, Consultation $consultation): void
    {
        $prescriptionsActives = [];

        foreach ($consultation->getPrescriptionsPrestations() as $prescription) {
            if (!$prescription instanceof PrescriptionPrestation) {
                continue;
            }

            if (!$prescription->isAFacturer()) {
                continue;
            }

            $prescriptionsActives[] = $prescription->getId();
        }

        foreach ($facture->getLignes() as $ligne) {
            $prescription = $ligne->getPrescriptionPrestation();

            if (!$prescription instanceof PrescriptionPrestation) {
                continue;
            }

            if (!\in_array($prescription->getId(), $prescriptionsActives, true)) {
                $facture->removeLigne($ligne);
                $this->em->remove($ligne);
            }
        }
    }
}