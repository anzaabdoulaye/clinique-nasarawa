<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\RendezVous;
use App\Enum\StatutConsultation;
use App\Enum\StatutRendezVous;
use App\Form\RendezVousType;
use App\Repository\ConsultationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rendez/vous')]
final class RendezVousController extends AbstractController
{
   #[Route(name: 'app_rendez_vous_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        RendezVousRepository $rendezVousRepository,
        EntityManagerInterface $em
    ): Response {
        $rv = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

    // consultation non obligatoire à la création
    $rv->setConsultation(null);

    // statut par défaut si vide
    if (!$rv->getStatut()) {
        $rv->setStatut(StatutRendezVous::EN_ATTENTE);
    }

    $em->persist($rv);
    $em->flush();

    return $this->redirectToRoute('app_rendez_vous_index');
}

        return $this->render('rendez_vous/index.html.twig', [
            'rendezVous' => $rendezVousRepository->findAll(),  
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(RendezVous $rendezVou): Response
    {
        return $this->render('rendez_vous/show.html.twig', [
            'rendez_vou' => $rendezVou,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rendezVous, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }
            return $this->redirectToRoute('app_rendez_vous_index');
        }

        // AJAX: renvoyer uniquement le contenu du form (pour la modale)
        if ($request->isXmlHttpRequest()) {
            return $this->render('rendez_vous/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        // Hors AJAX: page normale
        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendezVou->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendezVou);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/start-consultation', name: 'app_rendez_vous_start_consultation', methods: ['POST'])]
public function startConsultation(
    Request $request,
    RendezVous $rendezVous,
    ConsultationRepository $consultationRepo,
    EntityManagerInterface $em
): Response {
    if (!$this->isCsrfTokenValid('start_consultation_' . $rendezVous->getId(), (string)$request->request->get('_token'))) {
        throw $this->createAccessDeniedException('CSRF token invalide.');
    }

    // ✅ check DB
    $existing = $consultationRepo->findOneBy(['rendezVous' => $rendezVous]);
    if ($existing) {
        $this->addFlash('info', 'Consultation déjà créée.');
        return $this->redirectToRoute('app_consultation_show', ['id' => $existing->getId()]);
    }

    // ... tes checks statut RDV, patient, médecin, dossier

    $consultation = new Consultation();
    $consultation->setRendezVous($rendezVous); // sync auto RDV->consultation
    $consultation->setMedecin($rendezVous->getMedecin());
    $consultation->setDossierMedical($rendezVous->getPatient()->getDossierMedical());
    $consultation->setStatut(StatutConsultation::EN_COURS);

    $em->persist($consultation);
    $em->flush();

    return $this->redirectToRoute('app_consultation_show', ['id' => $consultation->getId()]);
}
}
