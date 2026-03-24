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
use App\Enum\StatutFacture;
use App\Enum\StatutHospitalisation;
use App\Enum\StatutPaiement;
use App\Repository\PatientRepository;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use App\Service\TreatmentAlertService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        ManagerRegistry $doctrine,
        PatientRepository $patientRepository,
        TreatmentAlertService $treatmentAlertService
    ): Response
    {
        $em = $doctrine->getManager();
        $patientSearch = trim((string) $request->query->get('patient_search', ''));
        $patientSearchResults = $patientSearch !== ''
            ? $patientRepository->searchDashboardPatients($patientSearch, 12)
            : [];

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
/* 
        
$receiptsToday = (float) $em->createQueryBuilder()
    ->select('COALESCE(SUM(f.montantTotal), 0)')
    ->from(Facture::class, 'f')
    ->where('f.dateEmission >= :today')
    ->andWhere('f.dateEmission < :tomorrow')
    ->setParameter('today', $today)
    ->setParameter('tomorrow', $tomorrow)
    ->getQuery()
    ->getSingleScalarResult();

$unpaidTotal = (float) $em->createQueryBuilder()
    ->select('COALESCE(SUM(f.resteAPayer), 0)')
    ->from(Facture::class, 'f')
    ->where('f.resteAPayer > 0')
    ->getQuery()
    ->getSingleScalarResult();

        $unpaidCount = (int) $em->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(Facture::class, 'f')
            ->where('f.statutPaiement = :enattente')
            ->setParameter('enattente', StatutPaiement::EN_ATTENTE->value)
            ->getQuery()
            ->getSingleScalarResult();

        
        $unpaidHighCount = (int) $em->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(Facture::class, 'f')
            ->where('f.statutPaiement = :enattente')
            ->andWhere('f.montant > :threshold')
            ->setParameter('enattente', StatutPaiement::EN_ATTENTE->value)
            ->setParameter('threshold', 20000)
            ->getQuery()
            ->getSingleScalarResult();
 */
        
        $lastConsultations = $em->createQueryBuilder()
            ->select('c','d','p','m')
            ->from(Consultation::class, 'c')
            ->join('c.dossierMedical', 'd')
            ->join('d.patient', 'p')
            ->join('c.medecin', 'm')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Flux patients - 7 jours glissants (consultations + urgences)
        $flowLabels = [];
        $flowConsults = [];
        $flowUrgences = [];
        for ($i = 6; $i >= 0; --$i) {
            $day = (clone $today)->modify("-{$i} days");
            $nextDay = (clone $day)->modify('+1 day');
            $flowLabels[] = $day->format('d M');

            $flowConsults[] = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Consultation::class, 'c')
                ->where('c.createdAt >= :day')
                ->andWhere('c.createdAt < :next')
                ->setParameter('day', $day)
                ->setParameter('next', $nextDay)
                ->getQuery()
                ->getSingleScalarResult();

            $flowUrgences[] = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Consultation::class, 'c')
                ->where('c.createdAt >= :day')
                ->andWhere('c.createdAt < :next')
                ->andWhere('c.statut = :encours')
                ->setParameter('day', $day)
                ->setParameter('next', $nextDay)
                ->setParameter('encours', StatutConsultation::EN_COURS->value)
                ->getQuery()
                ->getSingleScalarResult();
        }

        
        $sortiesToday = (int) $em->createQueryBuilder()
            ->select('COUNT(h.id)')
            ->from(Hospitalisation::class, 'h')
            ->where('h.dateSortie >= :today')
            ->andWhere('h.dateSortie < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        $transfers = (int) $em->createQueryBuilder()
            ->select('COUNT(h.id)')
            ->from(Hospitalisation::class, 'h')
            ->where('h.motifAdmission LIKE :term')
            ->setParameter('term', '%transfert%')
            ->getQuery()
            ->getSingleScalarResult();

        $treatmentAlerts = $treatmentAlertService->getDashboardAlerts();

        return $this->render('pages/index.html.twig', [
            'patient_search' => $patientSearch,
            'patient_search_results' => $patientSearchResults,
            'consultations_today' => $consultationsToday,
            'new_patients_today' => $newPatientsToday,
            'urgences_today' => $urgencesToday,
            'critical_cases' => $criticalCases,
            'rdv_today' => $rdvToday,
            'late_rdv' => $lateRdv,
            'hospitalisations_count' => $hospitalisationsCount,
            'beds_available' => $bedsAvailable,
            'occupancy_rate' => $occupancyRate,
            'receipts_today' => null,
            'unpaid_total' => null,
            'unpaid_count' => null,
            'unpaid_high_count' => null,
            'last_consultations' => $lastConsultations,
            'flow_labels' => $flowLabels,
            'flow_consultations' => $flowConsults,
            'flow_urgences' => $flowUrgences,
            'hosp_sorties_today' => $sortiesToday,
            'hosp_transfers' => $transfers,
            ...$treatmentAlerts,
        ]);
    }
}