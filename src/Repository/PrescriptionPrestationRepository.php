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

     public function findExamensLaboAPrendreEnCharge(): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::PAYE)
            ->setParameter('service', 'laboratoire')
            ->orderBy('pp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Examens labo en cours
     *
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboEnCours(): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::EN_COURS)
            ->setParameter('service', 'laboratoire')
            ->orderBy('pp.modifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Examens labo réalisés
     *
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboRealises(): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->andWhere('pp.statut = :statut')
            ->andWhere('tp.serviceExecution = :service')
            ->setParameter('statut', StatutPrescriptionPrestation::REALISE)
            ->setParameter('service', 'laboratoire')
            ->orderBy('pp.modifiedAt', 'DESC')
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

    /**
     * @return PrescriptionPrestation[]
     */
    public function findExamensLaboAvecResultatsParConsultation(int $consultationId): array
    {
        return $this->createQueryBuilder('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.dossierMedical', 'dm')->addSelect('dm')
            ->leftJoin('dm.patient', 'patientDossier')->addSelect('patientDossier')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'patientRdv')->addSelect('patientRdv')
            ->leftJoin('c.medecin', 'm')->addSelect('m')
            ->leftJoin('pp.tarifPrestation', 'tp')->addSelect('tp')
            ->leftJoin('pp.resultatLaboratoire', 'rl')->addSelect('rl')
            ->leftJoin('rl.lignes', 'rll')->addSelect('rll')
            ->andWhere('c.id = :consultationId')
            ->andWhere('tp.serviceExecution = :service')
            ->andWhere('rl.id IS NOT NULL')
            ->setParameter('consultationId', $consultationId)
            ->setParameter('service', 'laboratoire')
            ->orderBy('pp.id', 'ASC')
            ->addOrderBy('rll.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}