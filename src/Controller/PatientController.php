<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Form\PatientType;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/patient')]
final class PatientController extends AbstractController
{

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACCUEIL') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
     #[Route(name: 'app_patient_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PatientRepository $patientRepository,
        EntityManagerInterface $em
    ): Response {
        $patient = new Patient();

        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($patient);
            $em->flush();

            return $this->redirectToRoute('app_patient_index');
        }

        // Récupérer le filtre de période depuis les paramètres de requête
        $periodeFilter = $request->query->get('periode');
        if ($periodeFilter === null) {
            // Par défaut, filtrer sur les patients enregistrés récemment (30 derniers jours)
            $periodeFilter = 'recent';
        }

        if ($periodeFilter === 'recent') {
            $patients = $patientRepository->findPatientsRecent(30);
        } elseif ($periodeFilter === 'month') {
            $patients = $patientRepository->findPatientsThisMonth();
        } elseif ($periodeFilter === 'quarter') {
            $patients = $patientRepository->findPatientsRecent(90);
        } elseif ($periodeFilter === 'year') {
            $patients = $patientRepository->findPatientsRecent(365);
        } elseif ($periodeFilter === 'all') {
            $patients = $patientRepository->findAll();
        } else {
            $patients = $patientRepository->findAll();
        }

        return $this->render('patient/index.html.twig', [
            'patients' => $patients,
            'form' => $form->createView(),
            'periodeFilter' => $periodeFilter,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACCUEIL') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($patient);
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACCUEIL') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'])]
    public function show(Patient $patient): Response
    {
        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACCUEIL') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
    #[Route('/{id}/pdf', name: 'app_patient_pdf', methods: ['GET'])]
    public function printPdf(Patient $patient): Response
    {
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $html = $this->renderView('patient/print.html.twig', [
            'patient' => $patient,
            'logo_path' => $logoBase64,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="fiche-patient-%s.pdf"', $patient->getCode()),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACCUEIL') or is_granted('ROLE_MEDECIN')"
))]
   #[Route('/{id}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Patient $patient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        // ---------- CAS AJAX (MODAL) ----------
        if ($request->isXmlHttpRequest()) {

            // POST AJAX validé => JSON (PAS de redirect)
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Patient mis à jour avec succès.'
                ]);
            }

            // GET AJAX (ou POST avec erreurs) => renvoyer uniquement le form (sans layout)
            return $this->render('patient/edit.html.twig', [
                'form' => $form->createView(),
                'patient' => $patient,
            ]);
        }

        // ---------- CAS NORMAL (PAGE COMPLETE) ----------
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/edit.html.twig', [
            'patient' => $patient,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_patient_delete', methods: ['POST'])]
    public function delete(Request $request, Patient $patient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$patient->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
    }
}
