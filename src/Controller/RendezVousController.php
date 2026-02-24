<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Enum\StatutRendezVous;
use App\Form\RendezVousType;
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
            $em->persist($rv);
            $em->flush();

            return $this->redirectToRoute('app_rendez_vous_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            // 1) consultation non obligatoire à la création
            $rv->setConsultation(null);

            // 2) statut par défaut si vide (ex: "EN_ATTENTE")
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
}
