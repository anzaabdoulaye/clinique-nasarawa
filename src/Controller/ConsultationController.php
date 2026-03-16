<?php

namespace App\Controller;

use App\Entity\BonExamen;
use App\Entity\BonExamenLigne;
use App\Entity\Consultation;
use App\Entity\PrescriptionPrestation;
use  App\Entity\Utilisateur;
use App\Enum\StatutConsultation;
use App\Form\ConsultationType;
use App\Form\PrescriptionPrestationType;
use App\Repository\ConsultationRepository;
use App\Repository\BonExamenRepository;
use App\Service\BillingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/consultation')]
final class ConsultationController extends AbstractController
{
    #[Route(name: 'app_consultation_index', methods: ['GET', 'POST'])]
public function index(
    Request $request,
    ConsultationRepository $consultationRepository,
    EntityManagerInterface $em
): Response {
    $consultation = new Consultation();

    $form = $this->createForm(ConsultationType::class, $consultation, [
        'context' => 'admin',
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (null === $consultation->getDossierMedical()) {
            $form->addError(new FormError('Le dossier médical est requis.'));
        } else {
            if (null === $consultation->getMedecin() && $this->getUser() instanceof Utilisateur) {
                $consultation->setMedecin($this->getUser());
            }

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'Consultation créée avec succès.');

            return $this->redirectToRoute('app_consultation_index');
        }
    }

    $q = trim((string) $request->query->get('q', ''));

    if ($q !== '') {
        $consultations = $consultationRepository->searchByDossierOrPatientCode($q);
    } else {
        $consultations = $consultationRepository->findBy([], ['createdAt' => 'DESC']);
    }

    return $this->render('consultation/index.html.twig', [
        'consultations' => $consultations,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation, BonExamenRepository $bonRepo): Response
    {
        $bonsLabo = $bonRepo->findBy(['consultation' => $consultation], ['id' => 'DESC']);
        return $this->render('consultation/show.html.twig', [
            'bonsLabo' => $bonsLabo,
            'consultation' => $consultation,
        ]);
    }


    #[Route('/consultation/{id}/prestation/new', name: 'app_prescription_prestation_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    Consultation $consultation,
    EntityManagerInterface $em
): Response {
    $prescription = new PrescriptionPrestation();
    $prescription->setConsultation($consultation);

    $form = $this->createForm(PrescriptionPrestationType::class, $prescription, [
        'action' => $this->generateUrl('app_prescription_prestation_new', ['id' => $consultation->getId()]),
        'method' => 'POST',
    ]);

    $form->handleRequest($request);

    if ($request->isXmlHttpRequest()) {
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($prescription);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Prestation ajoutée avec succès.',
            ]);
        }

        return $this->render('consultation/prestations/_form.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
            'prescription' => $prescription,
        ]);
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($prescription);
        $em->flush();

        return $this->redirectToRoute('app_consultation_show', [
            'id' => $consultation->getId(),
        ]);
    }

    return $this->render('consultation/prestations/new.html.twig', [
        'form' => $form->createView(),
        'consultation' => $consultation,
        'prescription' => $prescription,
    ]);
}

#[Route('/prestation/{id}/edit', name: 'app_prescription_prestation_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    PrescriptionPrestation $prescription,
    EntityManagerInterface $em
): Response {
    $form = $this->createForm(PrescriptionPrestationType::class, $prescription, [
        'action' => $this->generateUrl('app_prescription_prestation_edit', ['id' => $prescription->getId()]),
        'method' => 'POST',
    ]);

    $form->handleRequest($request);

    if ($request->isXmlHttpRequest()) {
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Prestation modifiée avec succès.',
            ]);
        }

        return $this->render('consultation/prestations/_form.html.twig', [
            'form' => $form->createView(),
            'consultation' => $prescription->getConsultation(),
            'prescription' => $prescription,
        ]);
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();

        return $this->redirectToRoute('app_consultation_show', [
            'id' => $prescription->getConsultation()->getId(),
        ]);
    }

    return $this->render('consultation/prestations/edit.html.twig', [
        'form' => $form->createView(),
        'consultation' => $prescription->getConsultation(),
        'prescription' => $prescription,
    ]);
}



   #[Route('/{id}/medical', name: 'app_consultation_medical_edit', methods: ['GET', 'POST'])]
public function editMedical(
    Request $request,
    Consultation $consultation,
    EntityManagerInterface $em
): Response {
    if (\in_array($consultation->getStatut(), [StatutConsultation::CLOTURE, StatutConsultation::ANNULE], true)) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Consultation clôturée ou annulée : modification interdite.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->addFlash('warning', 'Consultation clôturée ou annulée : modification interdite.');

        return $this->redirectToRoute('app_consultation_show', [
            'id' => $consultation->getId(),
        ]);
    }

    $form = $this->createForm(ConsultationType::class, $consultation, [
        'context' => 'medical',
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
    $em->flush();

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true,
            'message' => 'Données médicales enregistrées.',
            'data' => [
                'poids' => $consultation->getPoids(),
                'taille' => $consultation->getTaille(),
                'temperature' => $consultation->getTemperature(),
                'frequenceCardiaque' => $consultation->getFrequenceCardiaque(),
                'tensionArterielle' => $consultation->getTensionArterielle(),
                'motifs' => $consultation->getMotifs(),
                'histoire' => $consultation->getHistoire(),
                'examenClinique' => $consultation->getExamenClinique(),
                'diagnostic' => $consultation->getDiagnostic(),
                'cim10' => $consultation->getCim10() ? (string) $consultation->getCim10() : '-',
                'conduiteATenir' => $consultation->getConduiteATenir(),
                'modifiedAt' => $consultation->getModifiedAt()?->format('Y-m-d H:i'),
            ]
        ]);
    }

        $this->addFlash('success', 'Données médicales enregistrées.');
        return $this->redirectToRoute('app_consultation_show', ['id' => $consultation->getId()]);
    }

    if ($form->isSubmitted() && !$form->isValid()) {
        if ($request->isXmlHttpRequest()) {
            return $this->render('consultation/_medical_form.html.twig', [
                'consultation' => $consultation,
                'form' => $form->createView(),
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs.');
    }

    if ($request->isXmlHttpRequest()) {
        return $this->render('consultation/_medical_form.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    return $this->render('consultation/medical_edit.html.twig', [
        'consultation' => $consultation,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_consultation_delete', methods: ['POST'])]
    public function delete(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consultation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($consultation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_consultation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/consultation/{id}/facture', name: 'app_consultation_facture', methods: ['GET', 'POST'])]
    public function facture(Consultation $consultation, Request $request, BillingService $billing, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $forfait = (float) ($request->request->get('forfait', 0));
            $facture = $billing->generateDraftInvoice($consultation, $forfait);
            $em->flush();

            return $this->json([
                'success' => true,
                'html' => $this->renderView('facture/_modal_facture.html.twig', [
                    'consultation' => $consultation,
                    'facture' => $facture,
                ]),
            ]);
        }

        // GET
        $facture = $billing->generateDraftInvoice($consultation, 0);
        $em->flush();

        return $this->render('facture/_modal_facture.html.twig', [
            'consultation' => $consultation,
            'facture' => $facture,
        ]);
    }

    #[Route('/consultation/{id}/examens/bon', name: 'app_consultation_examens_bon', methods: ['GET'])]
    public function bonExamens(Consultation $consultation): Response
    {
        $rdv = $consultation->getRendezVous();
        $patient = $rdv?->getPatient();      // adapte si ton patient est ailleurs
        $medecin = $consultation->getMedecin();

        return $this->render('examen/bon.html.twig', [
            'consultation' => $consultation,
            'patient' => $patient,
            'medecin' => $medecin,
            'examens' => $consultation->getExamensDemandes(),
        ]);
    }

    #[Route('/consultation/{id}/labo/bon/new', name: 'app_consultation_labo_bon_new', methods: ['GET', 'POST'])]
    public function newBonFromConsultation(
        Consultation $consultation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $rdv = $consultation->getRendezVous();
        $patient = $rdv?->getPatient();

        if (!$patient) {
            throw $this->createNotFoundException('Patient introuvable via le rendez-vous.');
        }

        if ($request->isMethod('POST')) {
            $bon = new BonExamen();
            $bon->setConsultation($consultation);
            $bon->setPatient($patient);
            $bon->setMedecin($consultation->getMedecin()); // adapte si getter différent
            $bon->setNote($request->request->get('note') ?: null);

            $libelles = $request->request->all('examens'); // array
            foreach ($libelles as $lib) {
                $lib = trim((string) $lib);
                if ($lib === '') continue;

                $ligne = (new BonExamenLigne())
                    ->setLibelle($lib)
                    ->setUrgence(false);

                $bon->addLigne($ligne);
            }

            if ($bon->getLignes()->count() === 0) {
                return $this->json(['success' => false, 'message' => 'Ajoutez au moins un examen.'], 422);
            }

            $em->persist($bon);
            $em->flush();

            return $this->json(['success' => true, 'bonId' => $bon->getId()]);
        }

        return $this->render('laboratoire/bons/_modal_new_from_consultation.html.twig', [
            'consultation' => $consultation,
            'patient' => $patient,
        ]);
    }

    #[Route('/consultation/{id}/labo/bon/modal', name: 'app_consultation_labo_bon_modal', methods: ['GET', 'POST'])]
public function laboBonModal(
    Consultation $consultation,
    Request $request,
    EntityManagerInterface $em
): Response {
    $rdv = $consultation->getRendezVous();
    $patient = $rdv?->getPatient();

    if (!$patient) {
        return $this->json(['success' => false, 'message' => 'Patient introuvable via RDV.'], 422);
    }

    if ($request->isMethod('POST')) {
        $bon = new BonExamen();
        $bon->setConsultation($consultation);
        $bon->setPatient($patient);
        $bon->setMedecin($consultation->getMedecin());
        $bon->setNote($request->request->get('note') ?: null);

        $rows = $request->request->all('examens'); // examens[][libelle], examens[][urgence], examens[][note]
        $count = 0;

        foreach ($rows as $row) {
            $libelle = trim((string)($row['libelle'] ?? ''));
            if ($libelle === '') continue;

            $urgence = (bool)($row['urgence'] ?? false);
            $note = trim((string)($row['note'] ?? '')) ?: null;

            $ligne = (new BonExamenLigne())
                ->setLibelle($libelle)
                ->setUrgence($urgence)
                ->setNote($note);

            $bon->addLigne($ligne);
            $count++;
        }

        if ($count === 0) {
            return $this->json(['success' => false, 'message' => 'Ajoutez au moins un examen.'], 422);
        }

        $em->persist($bon);
        $em->flush();

        return $this->json([
            'success' => true,
            'bonId' => $bon->getId(),
            'listHtml' => $this->renderView('laboratoire/bons/_consultation_list.html.twig', [
                'consultation' => $consultation,
                'bons' => $em->getRepository(BonExamen::class)->findBy(
                    ['consultation' => $consultation],
                    ['id' => 'DESC']
                ),
            ]),
        ]);
    }

    return $this->render('laboratoire/bons/_modal_create.html.twig', [
        'consultation' => $consultation,
        'patient' => $patient,
    ]);
}

#[Route('/{id}/edit-admin', name: 'app_consultation_admin_edit', methods: ['GET', 'POST'])]
public function editAdmin(
    Request $request,
    Consultation $consultation,
    EntityManagerInterface $em
): Response {
    $form = $this->createForm(ConsultationType::class, $consultation, [
        'context' => 'admin',
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => 'Informations de base mises à jour.'
            ]);
        }

        $this->addFlash('success', 'Informations de base mises à jour.');
        return $this->redirectToRoute('app_consultation_index');
    }

    if ($request->isXmlHttpRequest()) {
        return $this->render('consultation/_admin_form.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    return $this->render('consultation/admin_edit.html.twig', [
        'consultation' => $consultation,
        'form' => $form->createView(),
    ]);
}

}