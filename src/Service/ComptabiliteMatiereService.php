<?php

namespace App\Service;

use App\Entity\BonMatiere;
use App\Entity\BonMatiereLigne;
use App\Entity\Lot;
use App\Entity\Utilisateur;
use App\Entity\Vente;
use App\Enum\MotifMouvement;
use App\Enum\StatutBonMatiere;
use App\Enum\TypeBonMatiere;
use App\Repository\BonMatiereRepository;
use Doctrine\ORM\EntityManagerInterface;

class ComptabiliteMatiereService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BonMatiereRepository $bonMatiereRepository,
    ) {
    }

    /**
     * @param array<int, array{
     *     medicament: \App\Entity\Medicament,
     *     lot?: ?Lot,
    *     numeroLot?: ?string,
    *     datePeremption?: ?\DateTimeInterface,
     *     quantite: int,
     *     prixUnitaire?: ?float,
     *     observation?: ?string
     * }> $lignesData
     */
    public function creerBonEntree(
        array $lignesData,
        MotifMouvement $motif,
        ?Utilisateur $user,
        ?string $reference = null,
        ?string $observation = null,
        ?Utilisateur $ordonnateur = null,
    ): BonMatiere {
        $bon = $this->initialiserBon(
            type: TypeBonMatiere::ENTREE,
            motif: $motif,
            creePar: $user,
            reference: $reference,
            observation: $observation,
            ordonnateur: $ordonnateur
        );

        $this->ajouterLignesAuBon($bon, $lignesData);

        $this->em->persist($bon);
        $this->em->flush();

        return $bon;
    }

    /**
     * @param array<int, array{
     *     medicament: \App\Entity\Medicament,
     *     lot?: ?Lot,
    *     numeroLot?: ?string,
    *     datePeremption?: ?\DateTimeInterface,
     *     quantite: int,
     *     prixUnitaire?: ?float,
     *     observation?: ?string
     * }> $lignesData
     */
    public function creerBonSortieDefinitive(
        array $lignesData,
        MotifMouvement $motif,
        ?Utilisateur $user,
        ?string $reference = null,
        ?string $observation = null,
        ?Utilisateur $ordonnateur = null,
        ?Vente $vente = null,
    ): BonMatiere {
        $bon = $this->initialiserBon(
            type: TypeBonMatiere::SORTIE_DEFINITIVE,
            motif: $motif,
            creePar: $user,
            reference: $reference,
            observation: $observation,
            ordonnateur: $ordonnateur
        );

        $bon->setVente($vente);

        $this->ajouterLignesAuBon($bon, $lignesData);

        $this->em->persist($bon);
        $this->em->flush();

        return $bon;
    }

    /**
     * @param array<int, array{
     *     medicament: \App\Entity\Medicament,
     *     lot?: ?Lot,
    *     numeroLot?: ?string,
    *     datePeremption?: ?\DateTimeInterface,
     *     quantite: int,
     *     prixUnitaire?: ?float,
     *     observation?: ?string
     * }> $lignesData
     */
    public function creerBonSortieProvisoire(
        array $lignesData,
        MotifMouvement $motif,
        ?Utilisateur $user,
        ?string $reference = null,
        ?string $observation = null,
        ?Utilisateur $ordonnateur = null,
    ): BonMatiere {
        $bon = $this->initialiserBon(
            type: TypeBonMatiere::SORTIE_PROVISOIRE,
            motif: $motif,
            creePar: $user,
            reference: $reference,
            observation: $observation,
            ordonnateur: $ordonnateur
        );

        $bon->setImpactStock(false);

        $this->ajouterLignesAuBon($bon, $lignesData);

        $this->em->persist($bon);
        $this->em->flush();

        return $bon;
    }

public function genererDepuisVente(Vente $vente, ?Utilisateur $user = null): BonMatiere
{
    if (!$vente->getId()) {
        throw new \RuntimeException('Impossible de générer un bon matière pour une vente non enregistrée.');
    }

    if ($vente->getLignes()->isEmpty()) {
        throw new \RuntimeException('Impossible de générer un bon matière depuis une vente sans lignes.');
    }

    $existingBon = $this->bonMatiereRepository->findVenteBon($vente);
    if ($existingBon) {
        return $existingBon;
    }

    $bon = $this->initialiserBon(
        type: TypeBonMatiere::SORTIE_DEFINITIVE,
        motif: MotifMouvement::VENTE,
        creePar: $user,
        reference: 'VENTE-' . $vente->getId(),
        observation: 'Bon généré automatiquement depuis la vente #' . $vente->getId()
    );

    $bon->setVente($vente);

    foreach ($vente->getLignes() as $index => $venteLigne) {
        $medicament = $venteLigne->getMedicament();
        $lot = $venteLigne->getLot();
        $quantite = $venteLigne->getQuantite();

        if (!$medicament) {
            throw new \RuntimeException(sprintf('La ligne %d de la vente ne possède pas de médicament.', $index + 1));
        }

        if (!$lot) {
            throw new \RuntimeException(sprintf(
                'La ligne %d (%s) ne possède pas de lot.',
                $index + 1,
                $medicament->getNom()
            ));
        }

        if ($lot->getMedicament()->getId() !== $medicament->getId()) {
            throw new \RuntimeException(sprintf(
                'La ligne %d contient un lot incohérent avec le médicament %s.',
                $index + 1,
                $medicament->getNom()
            ));
        }

        if ($quantite <= 0) {
            throw new \RuntimeException(sprintf('La ligne %d contient une quantité invalide.', $index + 1));
        }

        $ligne = new BonMatiereLigne();
        $ligne->setMedicament($medicament);
        $ligne->setLot($lot);
        $ligne->setQuantite($quantite);
        $ligne->setPrixUnitaire($venteLigne->getPrixUnitaire());
        $ligne->setObservation('Issue de la vente');
        $ligne->setVenteLigne($venteLigne);

        $bon->addLigne($ligne);
    }

    $this->em->persist($bon);
    $this->em->flush();

    return $bon;
}

public function creerEtValiderDepuisVente(Vente $vente, ?Utilisateur $user = null): BonMatiere
{
    $bon = $this->genererDepuisVente($vente, $user);

    return $this->validerBon($bon);
}

    public function validerBon(BonMatiere $bon): BonMatiere
    {
        if ($bon->getStatut() === StatutBonMatiere::VALIDE) {
            return $bon;
        }

        if ($bon->getStatut() === StatutBonMatiere::ANNULE) {
            throw new \RuntimeException('Impossible de valider un bon annulé.');
        }

        if ($bon->getLignes()->isEmpty()) {
            throw new \RuntimeException('Impossible de valider un bon sans ligne.');
        }

        foreach ($bon->getLignes() as $ligne) {
            $this->validerLigne($ligne, $bon->getType(), $bon->isImpactStock());
        }

        $bon->setStatut(StatutBonMatiere::VALIDE);

        $this->em->flush();

        return $bon;
    }

    public function annulerBon(BonMatiere $bon): BonMatiere
    {
        if ($bon->getStatut() === StatutBonMatiere::VALIDE) {
            throw new \RuntimeException('Un bon déjà validé ne peut pas être annulé directement. Prévoir un mouvement de régularisation.');
        }

        $bon->setStatut(StatutBonMatiere::ANNULE);
        $this->em->flush();

        return $bon;
    }

    private function initialiserBon(
        TypeBonMatiere $type,
        MotifMouvement $motif,
        ?Utilisateur $creePar,
        ?string $reference = null,
        ?string $observation = null,
        ?Utilisateur $ordonnateur = null,
    ): BonMatiere {
        $bon = new BonMatiere();
        $bon->setNumero($this->generateNumero($type));
        $bon->setType($type);
        $bon->setMotif($motif);
        $bon->setDateBon(new \DateTimeImmutable());
        $bon->setStatut(StatutBonMatiere::BROUILLON);
        $bon->setReferenceExterne($reference);
        $bon->setObservation($observation);
        $bon->setCreePar($creePar);
        $bon->setOrdonnateur($ordonnateur);

        if ($type === TypeBonMatiere::SORTIE_PROVISOIRE) {
            $bon->setImpactStock(false);
        } else {
            $bon->setImpactStock(true);
        }

        return $bon;
    }

    /**
     * @param array<int, array{
     *     medicament: \App\Entity\Medicament,
     *     lot?: ?Lot,
    *     numeroLot?: ?string,
    *     datePeremption?: ?\DateTimeInterface,
     *     quantite: int,
     *     prixUnitaire?: ?float,
     *     observation?: ?string
     * }> $lignesData
     */
    private function ajouterLignesAuBon(BonMatiere $bon, array $lignesData): void
    {
        if (empty($lignesData)) {
            throw new \RuntimeException('Le bon doit contenir au moins une ligne.');
        }

        foreach ($lignesData as $data) {
            if (!isset($data['medicament'], $data['quantite'])) {
                throw new \RuntimeException('Chaque ligne doit contenir un médicament et une quantité.');
            }

            $quantite = (int) $data['quantite'];

            if ($quantite <= 0) {
                throw new \RuntimeException('La quantité doit être supérieure à zéro.');
            }

            $lot = $data['lot'] ?? null;
            $prixUnitaire = $data['prixUnitaire'] ?? null;

            if (!$lot instanceof Lot && $bon->getType() === TypeBonMatiere::ENTREE) {
                $lot = new Lot();
                $lot->setMedicament($data['medicament']);
                $lot->setNumeroLot($data['numeroLot'] ?? null);
                $lot->setDatePeremption($data['datePeremption'] ?? null);
                $lot->setQuantite(0);
                $lot->setPrixAchat($prixUnitaire);

                $this->em->persist($lot);
            }

            if ($bon->getType() === TypeBonMatiere::ENTREE && $prixUnitaire !== null) {
                if ($lot instanceof Lot) {
                    $lot->setPrixAchat($prixUnitaire);
                }

                $data['medicament']->setPrixUnitaire($prixUnitaire);
            }

            $ligne = new BonMatiereLigne();
            $ligne->setMedicament($data['medicament']);
            $ligne->setLot($lot);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire($prixUnitaire);
            $ligne->setObservation($data['observation'] ?? null);

            $bon->addLigne($ligne);
        }
    }

    private function validerLigne(
        BonMatiereLigne $ligne,
        TypeBonMatiere $type,
        bool $impactStock
    ): void {
        $lot = $ligne->getLot();
        $quantite = $ligne->getQuantite();

        if ($quantite <= 0) {
            throw new \RuntimeException('Une ligne contient une quantité invalide.');
        }

        if (!$impactStock) {
            return;
        }

        if (!$lot) {
            throw new \RuntimeException(sprintf(
                'Aucun lot défini pour le médicament "%s".',
                $ligne->getMedicament()?->getNom() ?? 'inconnu'
            ));
        }

        if ($type === TypeBonMatiere::ENTREE) {
            $lot->setQuantite($lot->getQuantite() + $quantite);
            return;
        }

        if ($type === TypeBonMatiere::SORTIE_DEFINITIVE) {
            if ($lot->getQuantite() < $quantite) {
                throw new \RuntimeException(sprintf(
                    'Stock insuffisant pour le lot "%s" du médicament "%s". Stock actuel : %d, quantité demandée : %d.',
                    $lot->getNumeroLot() ?? ('#' . $lot->getId()),
                    $ligne->getMedicament()?->getNom() ?? 'inconnu',
                    $lot->getQuantite(),
                    $quantite
                ));
            }

            $lot->setQuantite($lot->getQuantite() - $quantite);
            return;
        }

        // SORTIE_PROVISOIRE => aucun impact stock
    }

    private function generateNumero(TypeBonMatiere $type): string
    {
        $prefixe = $this->getPrefixeByType($type);
        $year = (new \DateTimeImmutable())->format('Y');

        $lastBon = $this->bonMatiereRepository->findLastByTypeAndYear($type, (int) $year);

        $lastSequence = 0;

        if ($lastBon && preg_match('/(\d+)$/', $lastBon->getNumero(), $matches)) {
            $lastSequence = (int) $matches[1];
        }

        $nextSequence = $lastSequence + 1;

        return sprintf('%s-%s-%04d', $prefixe, $year, $nextSequence);
    }

    private function getPrefixeByType(TypeBonMatiere $type): string
    {
        return match ($type) {
            TypeBonMatiere::ENTREE => 'BE',
            TypeBonMatiere::SORTIE_DEFINITIVE => 'BSD',
            TypeBonMatiere::SORTIE_PROVISOIRE => 'BSP',
        };
    }
}