<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Hospitalisation;
use App\Entity\Facture;
use App\Enum\StatutConsultation;
use App\Enum\StatutHospitalisation;
use App\Enum\StatutPaiement;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();

        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');
        $now = new \DateTimeImmutable();

        
        $consultationsToday = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Consultation::class, 'c')
            ->where('c.createdAt >= :today')
            ->andWhere('c.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        
        $newPatientsToday = (int) $em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Patient::class, 'p')
            ->where('p.createdAt >= :today')
            ->andWhere('p.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        
        $rdvToday = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(RendezVous::class, 'r')
            ->where('r.dateHeure >= :today')
            ->andWhere('r.dateHeure < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        
        $lateRdv = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(RendezVous::class, 'r')
            ->where('r.dateHeure < :now')
            ->andWhere("r.statut != :termine")
            ->setParameter('now', $now)
            ->setParameter('termine', \App\Enum\StatutRendezVous::TERMINE->value)
            ->getQuery()
            ->getSingleScalarResult();

        
        $urgencesToday = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Consultation::class, 'c')
            ->where('c.createdAt >= :today')
            ->andWhere('c.createdAt < :tomorrow')
            ->andWhere('c.statut = :encours')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('encours', StatutConsultation::EN_COURS->value)
            ->getQuery()
            ->getSingleScalarResult();

        
        $criticalCases = (int) $em->createQueryBuilder()
            ->select('COUNT(h.id)')
            ->from(Hospitalisation::class, 'h')
            ->where('h.dateAdmission >= :today')
            ->andWhere('h.dateAdmission < :tomorrow')
            ->andWhere('h.statut = :encours')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('encours', StatutHospitalisation::EN_COURS->value)
            ->getQuery()
            ->getSingleScalarResult();

        
        $hospitalisationsCount = (int) $em->createQueryBuilder()
            ->select('COUNT(h.id)')
            ->from(Hospitalisation::class, 'h')
            ->where('h.statut = :encours')
            ->setParameter('encours', StatutHospitalisation::EN_COURS->value)
            ->getQuery()
            ->getSingleScalarResult();

        
        $capacity = 24;
        try {
            $capacity = (int) $this->getParameter('app.bed_capacity');
        } catch (ParameterNotFoundException $e) {
            
        }
        $bedsAvailable = max(0, $capacity - $hospitalisationsCount);
        $occupancyRate = $capacity > 0 ? (int) round(($hospitalisationsCount / $capacity) * 100) : 0;

        
        $receiptsToday = (float) $em->createQueryBuilder()
            ->select('COALESCE(SUM(f.montant), 0)')
            ->from(Facture::class, 'f')
            ->where('f.dateEmission >= :today')
            ->andWhere('f.dateEmission < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        
        $unpaidTotal = (float) $em->createQueryBuilder()
            ->select('COALESCE(SUM(f.montant), 0)')
            ->from(Facture::class, 'f')
            ->where('f.statutPaiement = :enattente')
            ->setParameter('enattente', StatutPaiement::EN_ATTENTE->value)
            ->getQuery()
            ->getSingleScalarResult();

        $unpaidCount = (int) $em->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(Facture::class, 'f')
            ->where('f.statutPaiement = :enattente')
            ->setParameter('enattente', StatutPaiement::EN_ATTENTE->value)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('pages/index.html.twig', [
            'consultations_today' => $consultationsToday,
            'new_patients_today' => $newPatientsToday,
            'urgences_today' => $urgencesToday,
            'critical_cases' => $criticalCases,
            'rdv_today' => $rdvToday,
            'late_rdv' => $lateRdv,
            'hospitalisations_count' => $hospitalisationsCount,
            'beds_available' => $bedsAvailable,
            'occupancy_rate' => $occupancyRate,
            'receipts_today' => $receiptsToday,
            'unpaid_total' => $unpaidTotal,
            'unpaid_count' => $unpaidCount,
        ]);
    }
}