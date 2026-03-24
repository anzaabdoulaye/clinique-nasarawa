<?php

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\PrescriptionPrestation;
use App\Enum\StatutPrescriptionPrestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionPrestation>
 */
class PrescriptionPrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionPrestation::class);
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findByConsultation(Consultation $consultation): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.consultation = :consultation')
            ->setParameter('consultation', $consultation)
            ->orderBy('pp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findAFacturerPourConsultation(Consultation $consultation): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.consultation = :consultation')
            ->andWhere('pp.aFacturer = :aFacturer')
            ->setParameter('consultation', $consultation)
            ->setParameter('aFacturer', true)
            ->orderBy('pp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findNonFacturees(): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.aFacturer = :aFacturer')
            ->andWhere('pp.statut = :statut')
            ->setParameter('aFacturer', true)
            ->setParameter('statut', 'prescrit')
            ->orderBy('pp.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function applyLaboratoireSearch($qb, ?string $search): void
    {
        if ($search && trim($search) !== '') {
            $search = mb_strtolower(trim($search));

            $qb->andWhere(
                'LOWER(p.code) LIKE :search
                 OR LOWER(p.telephone) LIKE :search
                 OR LOWER(dm.numeroDossier) LIKE :search'
            )
            ->setParameter('search', '%' . $search . '%');
        }
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboAPrendreEnCharge(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('p.dossierMedical', 'dm')->addSelect('dm')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::PAYE)
            ->setParameter('service', 'laboratoire');

        $this->applyLaboratoireSearch($qb, $search);

        return $qb->orderBy('pp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboEnCours(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('p.dossierMedical', 'dm')->addSelect('dm')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::EN_COURS)
            ->setParameter('service', 'laboratoire');

        $this->applyLaboratoireSearch($qb, $search);

        return $qb->orderBy('pp.modifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboRealises(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('p.dossierMedical', 'dm')->addSelect('dm')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::REALISE)
            ->setParameter('service', 'laboratoire');

        $this->applyLaboratoireSearch($qb, $search);

        return $qb->orderBy('pp.modifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExamensLaboPayesParConsultation(int $consultationId): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('c.id = :consultationId')
            ->andWhere('pp.statut IN (:statuts)')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('consultationId', $consultationId)
            ->setParameter('statuts', [
                StatutPrescriptionPrestation::PAYE,
                StatutPrescriptionPrestation::EN_COURS,
                StatutPrescriptionPrestation::REALISE,
            ])
            ->setParameter('service', 'laboratoire')
            ->orderBy('pp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}