<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Enum\StatutConsultation;
use App\Form\ConsultationType;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/consultation')]
final class ConsultationController extends AbstractController
{
    /* #[Route(name: 'app_consultation_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ConsultationRepository $consultationRepository,
        EntityManagerInterface $em
    ): Response {
        $consultation = new Consultation();
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($consultation);
            $em->flush();

            return $this->redirectToRoute('app_consultation_index');
        }

        return $this->render('consultation/index.html.twig', [
            'consultations' => $consultationRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        return $this->render('consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_consultation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Consultation $consultation,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        // ---------- CAS AJAX (MODAL) ----------
        if ($request->isXmlHttpRequest()) {

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Consultation mise à jour avec succès.'
                ]);
            }

            return $this->render('consultation/edit.html.twig', [
                'form' => $form->createView(),
                'consultation' => $consultation,
            ]);
        }

        // ---------- CAS NORMAL ----------
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_consultation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('consultation/edit.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_consultation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $consultation = new Consultation();
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($consultation);
            $entityManager->flush();

            return $this->redirectToRoute('app_consultation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('consultation/new.html.twig', [
            'consultation' => $consultation,
            'form' => $form,
        ]);
    } */

        #[Route('', name: 'app_consultation_index', methods: ['GET'])]
    public function index(ConsultationRepository $repo): Response
    {
        return $this->render('consultation/index.html.twig', [
            'consultations' => $repo->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        return $this->render('consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/medical', name: 'app_consultation_medical_edit', methods: ['GET', 'POST'])]
    public function editMedical(
        Request $request,
        Consultation $consultation,
        EntityManagerInterface $em
    ): Response {
        // Guard statut : pas modifiable si clôturée/annulée
        if (\in_array($consultation->getStatut(), [StatutConsultation::CLOTURE, StatutConsultation::ANNULE], true)) {
            $this->addFlash('warning', 'Consultation clôturée/annulée : modification interdite.');
            return $this->redirectToRoute('app_consultation_show', ['id' => $consultation->getId()]);
        }

        // IMPORTANT : Form dédié Phase C (à créer)
        $form = $this->createForm(ConsultationType::class, $consultation, [
            'context' => 'medical',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Données médicales enregistrées.');
            return $this->redirectToRoute('app_consultation_show', ['id' => $consultation->getId()]);
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
}
