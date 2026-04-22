<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Entity\FactureLigne;
use App\Entity\Paiement;
use App\Entity\PrescriptionPrestation;
use App\Entity\Utilisateur;
use App\Enum\ModePaiement;
use App\Enum\StatutFacture;
use App\Enum\StatutPrescriptionPrestation;
use App\Enum\TypePrestationPEC;
use App\Repository\FactureLigneRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;

class FacturationService
{
    private const CONSULTATION_LINE_TYPE = 'CONSULTATION';
    private const CONSULTATION_LINE_LABEL = 'Consultation';
    private const CONSULTATION_PRICE = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FactureRepository $factureRepository,
        private readonly FactureLigneRepository $factureLigneRepository,
        private readonly PriseEnChargeService $priseEnChargeService,
    ) {
    }


    private function assertConsultationModifiable(Consultation $consultation): void
    {
        if ($consultation->estCloturee()) {
            throw new \LogicException('Cette consultation est clôturée. Aucune nouvelle prestation ne peut être ajoutée.');
        }

        if ($consultation->estAnnulee()) {
            throw new \LogicException('Cette consultation est annulée. Aucune modification de facturation n’est autorisée.');
        }
    }

    public function initialiserOuRecupererFacture(Consultation $consultation): Facture
{
    $facture = $consultation->getFacture();

    if (!$facture instanceof Facture) {
        $this->assertConsultationModifiable($consultation);

        $facture = new Facture();
        $facture->setConsultation($consultation);
        $facture->setStatut(StatutFacture::BROUILLON);
        $facture->setDateEmission(new \DateTimeImmutable());
        $facture->setMontantTotal(0);
        $facture->setMontantPaye(0);
        $facture->setResteAPayer(0);
        $facture->setMontantTotalBrut(0);
        $facture->setMontantTotalPriseEnCharge(0);
        $facture->setMontantTotalPatient(0);
        $facture->setMontantPayePatient(0);
        $facture->setRestePatient(0);

        $consultation->setFacture($facture);
        $this->em->persist($facture);
    }

    // On ne recrée pas / n’ajoute pas de ligne consultation si la consultation est clôturée
    if ($consultation->estModifiable()) {
        $this->assurerLigneConsultation($facture);
    }

    $this->recalculerFacture($facture);

    return $facture;
}

    public function synchroniserDepuisPrescription(PrescriptionPrestation $prescription): Facture
{
    $consultation = $prescription->getConsultation();

    if (!$consultation instanceof Consultation) {
        throw new \LogicException('La prescription prestation doit être liée à une consultation.');
    }

    $this->assertConsultationModifiable($consultation);

    $facture = $this->initialiserOuRecupererFacture($consultation);

    if (!$prescription->isAFacturer()) {
        $ligneExistante = $this->trouverLigneParPrescription($prescription);

        if ($ligneExistante instanceof FactureLigne) {
            $facture->removeLigne($ligneExistante);
            $this->em->remove($ligneExistante);
        }

        $this->recalculerFacture($facture);
        return $facture;
    }

    $ligne = $this->trouverLigneParPrescription($prescription);

    if (!$ligne instanceof FactureLigne) {
        $ligne = new FactureLigne();
        $ligne->setFacture($facture);
        $ligne->setPrescriptionPrestation($prescription);

        $facture->addLigne($ligne);
        $this->em->persist($ligne);
    }

    $this->hydraterLigneDepuisPrescription($ligne, $prescription);

    if ($prescription->getStatut() === StatutPrescriptionPrestation::PRESCRIT) {
        $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
    }

    $this->recalculerFacture($facture);

    return $facture;
}

    public function synchroniserFactureDepuisConsultation(Consultation $consultation): Facture
{
    $this->assertConsultationModifiable($consultation);

    $facture = $this->initialiserOuRecupererFacture($consultation);

    foreach ($consultation->getPrescriptionsPrestations() as $prescription) {
        if (!$prescription instanceof PrescriptionPrestation) {
            continue;
        }

        $ligne = $this->trouverLigneParPrescription($prescription);

        if (!$prescription->isAFacturer()) {
            if ($ligne instanceof FactureLigne) {
                $facture->removeLigne($ligne);
                $this->em->remove($ligne);
            }
            continue;
        }

        if (!$ligne instanceof FactureLigne) {
            $ligne = new FactureLigne();
            $ligne->setFacture($facture);
            $ligne->setPrescriptionPrestation($prescription);

            $facture->addLigne($ligne);
            $this->em->persist($ligne);
        }

        $this->hydraterLigneDepuisPrescription($ligne, $prescription);

        if ($prescription->getStatut() === StatutPrescriptionPrestation::PRESCRIT) {
            $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
        }
    }

    $this->supprimerLignesOrphelines($facture, $consultation);
    $this->assurerLigneConsultation($facture);
    $this->recalculerFacture($facture);

    return $facture;
}

    public function ajouterPaiement(
        Facture $facture,
        int $montant,
        ModePaiement $mode,
        ?Utilisateur $effectuePar = null
    ): Paiement {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être supérieur à zéro.');
        }

        // Ajouter la ligne timbre lors du premier paiement (perception)
        if ($facture->getMontantPaye() === 0) {
            $this->assurerLigneTimbre($facture);
        }

        $this->recalculerFacture($facture);

        if ($montant > $facture->getRestePatient()) {
            throw new \InvalidArgumentException('Le montant ne doit pas dépasser le reste patient à payer.');
        }

        $paiement = new Paiement();
        $paiement->setFacture($facture);
        $paiement->setMontant($montant);
        $paiement->setMode($mode);
        $paiement->setPayeLe(new \DateTimeImmutable());
        $paiement->setEffectuePar($effectuePar);

        $facture->addPaiement($paiement);
        $this->em->persist($paiement);

        $this->recalculerFacture($facture);
        $this->mettreAJourStatutPrestations($facture);

        return $paiement;
    }

    public function recalculerFacture(Facture $facture): void
    {
        $patient = $facture->getConsultation()?->getDossierMedical()?->getPatient();

        $this->priseEnChargeService->recalculerFacture($facture, $patient);
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
                if ($prescription->getStatut() === StatutPrescriptionPrestation::PRESCRIT) {
                    $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
                }
                continue;
            }

            if (\in_array($statutFacture, [StatutFacture::BROUILLON, StatutFacture::NON_PAYE], true)) {
                if ($prescription->getStatut() === StatutPrescriptionPrestation::PAYE) {
                    $prescription->setStatut(StatutPrescriptionPrestation::FACTURE);
                }
                continue;
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
        $facture->setMontantPayePatient(0);

        $facture->setResteAPayer($facture->getMontantTotalPatient());
        $facture->setRestePatient($facture->getMontantTotalPatient());

        $this->mettreAJourStatutPrestations($facture);
    }

   public function supprimerDepuisPrescription(PrescriptionPrestation $prescription): void
{
    $consultation = $prescription->getConsultation();

    if (!$consultation instanceof Consultation) {
        return;
    }

    $this->assertConsultationModifiable($consultation);

    $facture = $consultation->getFacture();

    if (!$facture instanceof Facture) {
        return;
    }

    $ligne = $this->trouverLigneParPrescription($prescription);

    if ($ligne instanceof FactureLigne) {
        $facture->removeLigne($ligne);
        $this->em->remove($ligne);
    }

    $this->recalculerFacture($facture);

    $lignesNonConsultation = 0;
    foreach ($facture->getLignes() as $ligneRestante) {
        if ($ligneRestante->getPrescriptionPrestation() !== null) {
            $lignesNonConsultation++;
        }
    }

    if ($facture->getLignes()->isEmpty() || ($facture->getLignes()->count() === 1 && $lignesNonConsultation === 0)) {
        $this->assurerLigneConsultation($facture);
        $this->recalculerFacture($facture);
    }
}

    private function trouverLigneParPrescription(PrescriptionPrestation $prescription): ?FactureLigne
    {
        return $this->factureLigneRepository->findOneBy([
            'prescriptionPrestation' => $prescription,
        ]);
    }

    private function assurerLigneConsultation(Facture $facture): void
    {
        $ligneConsultation = null;

        foreach ($facture->getLignes() as $ligne) {
            if (
                $ligne->getPrescriptionPrestation() === null
                && $ligne->getType() === self::CONSULTATION_LINE_TYPE
            ) {
                $ligneConsultation = $ligne;
                break;
            }
        }

        if (!$ligneConsultation instanceof FactureLigne) {
            $ligneConsultation = new FactureLigne();
            $ligneConsultation->setFacture($facture);
            $facture->addLigne($ligneConsultation);
            $this->em->persist($ligneConsultation);
        }

        $ligneConsultation->setLibelle(self::CONSULTATION_LINE_LABEL);
        $ligneConsultation->setQuantite(1);
        $ligneConsultation->setPrixUnitaire(self::CONSULTATION_PRICE);
        $ligneConsultation->setType(self::CONSULTATION_LINE_TYPE);
        $ligneConsultation->setTypePrestationPEC(TypePrestationPEC::CONSULTATION);
    }

    private function assurerLigneTimbre(Facture $facture): void
    {
        $ligneTimbre = null;

        foreach ($facture->getLignes() as $ligne) {
            if (
                $ligne->getPrescriptionPrestation() === null
                && $ligne->getType() === 'TIMBRE'
            ) {
                $ligneTimbre = $ligne;
                break;
            }
        }

        if (!$ligneTimbre instanceof FactureLigne) {
            $ligneTimbre = new FactureLigne();
            $ligneTimbre->setFacture($facture);
            $facture->addLigne($ligneTimbre);
            $this->em->persist($ligneTimbre);
        }

        $ligneTimbre->setLibelle('Timbre');
        $ligneTimbre->setQuantite(1);
        $ligneTimbre->setPrixUnitaire(200);
        $ligneTimbre->setType('TIMBRE');
        $ligneTimbre->setTypePrestationPEC(TypePrestationPEC::AUTRE);
    }

    private function hydraterLigneDepuisPrescription(
        FactureLigne $ligne,
        PrescriptionPrestation $prescription
    ): void {
        $quantite = max(1, (int) $prescription->getQuantite());
        $prixUnitaire = max(0, (int) $prescription->getPrixReference());
        $typeLegacy = $prescription->getCategorieLabel();

        $ligne->setLibelle($prescription->getLibelle() ?? 'Prestation');
        $ligne->setQuantite($quantite);
        $ligne->setPrixUnitaire($prixUnitaire);
        $ligne->setType($typeLegacy);
        $ligne->setTypePrestationPEC(
            $this->determinerTypePrestationPECDepuisPrescription($prescription)
        );

        // Valeur immédiatement cohérente avant recalcul global
        $ligne->setTotal($quantite * $prixUnitaire);
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

    private function determinerTypePrestationPECDepuisPrescription(
        PrescriptionPrestation $prescription
    ): TypePrestationPEC {
        $categorie = mb_strtoupper((string) ($prescription->getCategorieLabel() ?? ''));

        return match ($categorie) {
            'CONSULTATION' => TypePrestationPEC::CONSULTATION,
            'EXAMEN',
            'EXAMEN_BIOLOGIQUE',
            'LABO',
            'LABORATOIRE' => TypePrestationPEC::EXAMEN,
            'HOSPITALISATION' => TypePrestationPEC::HOSPITALISATION,
            'SOIN' => TypePrestationPEC::SOIN,
            'ACTE' => TypePrestationPEC::ACTE,
            'CONSOMMABLE' => TypePrestationPEC::CONSOMMABLE,
            default => TypePrestationPEC::AUTRE,
        };
    }
}
