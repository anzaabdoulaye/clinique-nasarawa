<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FacturePECRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * Retourne les données du rapport des prises en charge par organisme.
     *
     * @return array{
     *     lignes: array<int, array{
     *         organisme_id: int|null,
     *         organisme: string,
     *         code: string,
     *         nombre_factures: int,
     *         montant_total_pec: int,
     *         montant_total_patient: int,
     *         montant_total_brut: int
     *     }>,
     *     journal: array<int, array{
     *         facture_id: int,
     *         date_emission: \DateTimeImmutable|null,
     *         date_paiement: \DateTimeImmutable|null,
     *         patient: string,
     *         code_patient: string,
     *         dossier: string,
     *         organisme: string,
     *         code_organisme: string,
     *         montant_brut: int,
     *         montant_pec: int,
     *         montant_patient: int,
     *         montant_paye_patient: int,
     *         reste_patient: int,
     *         statut: string
     *     }>,
     *     totalMontantPec: int,
     *     totalFactures: int,
     *     totalOrganismes: int
     * }
     */
    public function getRapportPrisesEnChargeParOrganisme(
        ?string $search = null,
        ?int $organismeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): array {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.organismePriseEnCharge', 'o')
            ->leftJoin('f.consultation', 'c')
            ->leftJoin('c.dossierMedical', 'd')
            ->leftJoin('d.patient', 'p')
            ->addSelect('o', 'c', 'd', 'p')
            ->andWhere('f.priseEnChargeActive = :pecActive')
            ->andWhere('f.organismePriseEnCharge IS NOT NULL')
            ->setParameter('pecActive', true);

        if ($search !== null && trim($search) !== '') {
            $search = trim($search);

            $qb->andWhere(
                '(
                    p.nom LIKE :search
                    OR p.prenom LIKE :search
                    OR CONCAT(p.nom, \' \', p.prenom) LIKE :search
                    OR CONCAT(p.prenom, \' \', p.nom) LIKE :search
                    OR p.telephone LIKE :search
                    OR p.code LIKE :search
                    OR d.numeroDossier LIKE :search
                )'
            )
            ->setParameter('search', '%' . $search . '%');
        }

        if ($organismeId) {
            $qb->andWhere('o.id = :organismeId')
               ->setParameter('organismeId', $organismeId);
        }

        if (!empty($dateDebut)) {
            $du = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateDebut . ' 00:00:00');
            if ($du !== false) {
                $qb->andWhere('f.dateEmission >= :du')
                   ->setParameter('du', $du);
            }
        }

        if (!empty($dateFin)) {
            $au = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateFin . ' 23:59:59');
            if ($au !== false) {
                $qb->andWhere('f.dateEmission <= :au')
                   ->setParameter('au', $au);
            }
        }

        $qb->orderBy('f.dateEmission', 'DESC');

        /** @var Facture[] $factures */
        $factures = $qb->getQuery()->getResult();

        $groupes = [];
        $journal = [];
        $totalMontantPec = 0;
        $totalFactures = 0;

        foreach ($factures as $facture) {
            $organisme = $facture->getOrganismePriseEnCharge();
            $consultation = $facture->getConsultation();
            $dossier = $consultation?->getDossierMedical();
            $patient = $dossier?->getPatient();

            $organismeIdValue = $organisme?->getId();
            $organismeNom = $organisme?->getNom() ?? 'Organisme non renseigné';
            $organismeCode = $organisme?->getCode() ?? '-';

            $montantBrut = $facture->getMontantTotalBrut();
            $montantPec = $facture->getMontantTotalPriseEnCharge();
            $montantPatient = $facture->getMontantTotalPatient();
            $montantPayePatient = $facture->getMontantPayePatient();
            $restePatient = $facture->getRestePatient();

            $key = $organismeIdValue ?? 0;

            if (!isset($groupes[$key])) {
                $groupes[$key] = [
                    'organisme_id' => $organismeIdValue,
                    'organisme' => $organismeNom,
                    'code' => $organismeCode,
                    'nombre_factures' => 0,
                    'montant_total_pec' => 0,
                    'montant_total_patient' => 0,
                    'montant_total_brut' => 0,
                ];
            }

            $groupes[$key]['nombre_factures']++;
            $groupes[$key]['montant_total_pec'] += $montantPec;
            $groupes[$key]['montant_total_patient'] += $montantPatient;
            $groupes[$key]['montant_total_brut'] += $montantBrut;

            $nomPatient = '-';
            if ($patient) {
                $nomPatient = trim(($patient->getNom() ?? '') . ' ' . ($patient->getPrenom() ?? ''));
            }

            $journal[] = [
                'facture_id' => $facture->getId(),
                'date_emission' => $facture->getDateEmission(),
                'date_paiement' => $facture->getDatePaiement(),
                'patient' => $nomPatient !== '' ? $nomPatient : '-',
                'code_patient' => $patient?->getCode() ?? '-',
                'dossier' => $dossier?->getNumeroDossier() ?? '-',
                'organisme' => $organismeNom,
                'code_organisme' => $organismeCode,
                'montant_brut' => $montantBrut,
                'montant_pec' => $montantPec,
                'montant_patient' => $montantPatient,
                'montant_paye_patient' => $montantPayePatient,
                'reste_patient' => $restePatient,
                'statut' => $facture->getStatut()->value,
            ];

            $totalMontantPec += $montantPec;
            $totalFactures++;
        }

        usort($groupes, static function (array $a, array $b): int {
            return $b['montant_total_pec'] <=> $a['montant_total_pec'];
        });

        $totalOrganismes = count($groupes);

        return [
            'lignes' => array_values($groupes),
            'journal' => $journal,
            'totalMontantPec' => $totalMontantPec,
            'totalFactures' => $totalFactures,
            'totalOrganismes' => $totalOrganismes,
        ];
    }

    /**
     * Retourne uniquement le journal détaillé des prises en charge filtrées.
     */
    public function getJournalPrisesEnChargeParOrganisme(
        ?string $search = null,
        ?int $organismeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): array {
        $rapport = $this->getRapportPrisesEnChargeParOrganisme(
            $search,
            $organismeId,
            $dateDebut,
            $dateFin
        );

        return $rapport['journal'];
    }
}