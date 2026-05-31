<?php

namespace App\Controller;

use App\Entity\DossierMedical;
use App\Entity\ObservationMedicale;
use App\Form\ObservationMedicaleType;
use App\Form\DossierMedicalType;
use App\Repository\ConsultationRepository;
use App\Repository\DossierMedicalRepository;
use App\Repository\HospitalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dossier-medical')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class DossierMedicalController extends AbstractController
{
    #[Route('', name: 'app_dossier_medical_index', methods: ['GET'])]
    public function index(DossierMedicalRepository $dossierMedicalRepository): Response
    {
        return $this->render('dossier_medical/index.html.twig', [
            'dossier_medicals' => $dossierMedicalRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

   #[Route('/{id}', name: 'app_dossier_medical_show', methods: ['GET', 'POST'])] // ⚠️ Ajout de POST
    public function show(
        Request $request, // ⚠️ Ajout de la Request
        EntityManagerInterface $entityManager, // ⚠️ Ajout de l'EntityManager
        DossierMedical $dossierMedical,
        ConsultationRepository $consultationRepository,
        HospitalisationRepository $hospitalisationRepository
    ): Response {
        $consultations = $consultationRepository->findBy(
            ['dossierMedical' => $dossierMedical],
            ['createdAt' => 'DESC']
        );

        $hospitalisations = $hospitalisationRepository->findBy(
            ['dossierMedical' => $dossierMedical],
            ['createdAt' => 'DESC']
        );

        // --- DÉBUT : LOGIQUE D'AJOUT D'OBSERVATION ---
        $observation = new \App\Entity\ObservationMedicale();
        $formObservation = $this->createForm(\App\Form\ObservationMedicaleType::class, $observation);
        $formObservation->handleRequest($request);

        if ($formObservation->isSubmitted() && $formObservation->isValid()) {
            // Injections sécurisées automatiques
            $observation->setDossier($dossierMedical);
            $observation->setMedecinAuteur($this->getUser());

            $entityManager->persist($observation);
            $entityManager->flush();

            $this->addFlash('success', 'L\'observation a été ajoutée au dossier avec succès.');

            // Redirection pour éviter la double soumission du formulaire (F5)
            return $this->redirectToRoute('app_dossier_medical_show', ['id' => $dossierMedical->getId()]);
        }
        // --- FIN : LOGIQUE D'AJOUT D'OBSERVATION ---

        $derniereConsultation = $consultations[0] ?? null;
        $derniereHospitalisation = $hospitalisations[0] ?? null;

        return $this->render('dossier_medical/show.html.twig', [
            'dossier' => $dossierMedical,
            'patient' => $dossierMedical->getPatient(),
            'consultations' => $consultations,
            'hospitalisations' => $hospitalisations,
            'derniereConsultation' => $derniereConsultation,
            'derniereHospitalisation' => $derniereHospitalisation,
            'formObservation' => $formObservation->createView(), // ⚠️ Envoi du formulaire à la vue
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dossier_medical_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        DossierMedical $dossierMedical,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(DossierMedicalType::class, $dossierMedical);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dossierMedical->syncMedicalDataToPatient();
            $entityManager->flush();

            $this->addFlash('success', 'Le dossier médical a été mis à jour avec succès.');

            // ✅ NOUVEAU : Redirection dynamique si un paramètre "redirect_to" est présent
            $redirectTo = $request->query->get('redirect_to');
            if ($redirectTo) {
                return $this->redirect($redirectTo);
            }

            // Fallback (comportement par défaut)
            return $this->redirectToRoute('app_dossier_medical_show', [
                'id' => $dossierMedical->getId(),
            ]);
        }

        return $this->render('dossier_medical/edit.html.twig', [
            'dossier' => $dossierMedical,
            'patient' => $dossierMedical->getPatient(),
            'form' => $form->createView(),
        ]);
    }
}