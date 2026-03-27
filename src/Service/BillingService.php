<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Entity\FactureLigne;
use App\Entity\Paiement;
use App\Enum\ModePaiement;
use App\Enum\StatutBonExamen;
use App\Enum\StatutExamenDemande;
use App\Enum\StatutFacture;
use App\Repository\BonExamenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class BillingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BonExamenRepository $bonRepo,
    ) {}

    public function generateDraftInvoice(Consultation $c, int $forfaitConsultation = 0): Facture
    {
        $facture = $c->getFacture();

        if (!$facture) {
            $facture = new Facture();
            $facture->setConsultation($c);
            $facture->setDateEmission(new \DateTimeImmutable());
            $facture->setStatut(StatutFacture::BROUILLON);

            $this->em->persist($facture);

            // si Consultation a setFacture()
            if (method_exists($c, 'setFacture')) {
                $c->setFacture($facture);
            }
        }

        // Si déjà payée => on ne touche pas
        if ($facture->getStatut() === StatutFacture::PAYE) {
            return $facture;
        }

        foreach (iterator_to_array($facture->getLignes()) as $ligne) {
            $facture->removeLigne($ligne);
        }

        // ============ 1) Forfait consultation ============
        if ($forfaitConsultation > 0) {
            $l = (new FactureLigne())
                ->setType('CONSULTATION')
                ->setLibelle('Consultation')
                ->setQuantite(1)
                ->setPrixUnitaire($forfaitConsultation);

            $this->recalcLine($l);
            $facture->addLigne($l);
        }

        // ============ 2) Actes réalisés ============
        foreach ($c->getActesRealises() as $acte) {
            $puStr = $acte->getPrixUnitaire(); // decimal string|null
            $pu = $this->toIntFcfa($puStr);

            if ($pu <= 0) {
                continue;
            }

            $qte = (int)($acte->getQuantite() ?: 1);

            $l = (new FactureLigne())
                ->setType('ACTE')
                ->setLibelle($acte->getLibelle())
                ->setQuantite($qte)
                ->setPrixUnitaire($pu);

            $this->recalcLine($l);
            $facture->addLigne($l);
        }

        // ============ 3) Examens (ancien modèle ExamenDemande) ============
        // Tu peux garder ou supprimer ce bloc selon ton choix.
        // Si tu passes totalement sur BonExamen, tu peux le retirer.
        foreach ($c->getExamensDemandes() as $ex) {
            if ($ex->getStatut() !== StatutExamenDemande::RESULTAT_RECU) {
                continue;
            }

            $puStr = $ex->getPrixUnitaire(); // decimal string|null
            $pu = $this->toIntFcfa($puStr);
            if ($pu <= 0) {
                continue;
            }

            $l = (new FactureLigne())
                ->setType('EXAMEN')
                ->setLibelle($ex->getLibelle())
                ->setQuantite(1)
                ->setPrixUnitaire($pu);

            $this->recalcLine($l);
            $facture->addLigne($l);
        }

        // ============ 4) Examens (nouveau module labo BonExamen) ============
        $bons = $this->bonRepo->findBy(
            ['consultation' => $c, 'statut' => StatutBonExamen::RESULTATS_DISPONIBLES],
            ['id' => 'DESC']
        );

        foreach ($bons as $bon) {
            foreach ($bon->getLignes() as $bl) {
                // Optionnel mais recommandé : ne facturer que si résultat saisi
                if (method_exists($bl, 'isResultatValide') && !$bl->isResultatValide()) {
                    continue;
                }

                $puStr = $bl->getPrixUnitaire(); // decimal string|null
                $pu = $this->toIntFcfa($puStr);
                if ($pu <= 0) {
                    continue;
                }

                $l = (new FactureLigne())
                    ->setType('EXAMEN')
                    ->setLibelle($bl->getLibelle())
                    ->setQuantite(1)
                    ->setPrixUnitaire($pu);

                $this->recalcLine($l);
                $facture->addLigne($l);
            }
        }

        $facture->recalculerMontants();

        return $facture;
    }

    public function payInvoice(Facture $facture, ModePaiement $mode): void
    {
        if ($facture->getStatut() === StatutFacture::PAYE || $facture->getResteAPayer() <= 0) {
            return;
        }

        $paiement = (new Paiement())
            ->setFacture($facture)
            ->setMontant($facture->getResteAPayer())
            ->setMode($mode)
            ->setPayeLe(new \DateTimeImmutable());

        $facture->addPaiement($paiement);
        $this->em->persist($paiement);
        $facture->recalculerMontants();
    }

    private function recalcLine(FactureLigne $l): void
    {
        // Si tu as déjà $l->recalc() dans ton entity, garde-le.
        if (method_exists($l, 'recalc')) {
            $l->recalc();
            return;
        }

        $qte = (int)$l->getQuantite();
        $pu = (int)$l->getPrixUnitaire();
        $l->setTotal($qte * $pu);
    }

    private function toIntFcfa(?string $decimal): int
    {
        if ($decimal === null) return 0;
        $decimal = trim($decimal);
        if ($decimal === '') return 0;

        // Ex: "7000.00" => 7000
        // Ex: "7000" => 7000
        // On arrondit proprement
        return (int) round((float) $decimal);
    }
}