<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Form\LotType;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacie/lot')]
final class LotController extends AbstractController
{
    #[Route(name: 'app_lot_index', methods: ['GET'])]
    public function index(LotRepository $lotRepository): Response
    {
        return $this->render('pharmacie/lot/index.html.twig', [
            'lots' => $lotRepository->findBy([], ['datePeremption' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_lot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $lot = new Lot();
        $form = $this->createForm(LotType::class, $lot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($lot);
            $em->flush();

            $this->addFlash('success', 'Lot ajouté.');
            return $this->redirectToRoute('app_lot_index');
        }

        return $this->render('pharmacie/lot/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lot_show', methods: ['GET'])]
    public function show(Lot $lot): Response
    {
        return $this->render('pharmacie/lot/show.html.twig', [
            'lot' => $lot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lot $lot, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LotType::class, $lot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Lot mis à jour.');
            return $this->redirectToRoute('app_lot_index');
        }

        return $this->render('pharmacie/lot/edit.html.twig', [
            'lot' => $lot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lot_delete', methods: ['POST'])]
    public function delete(Request $request, Lot $lot, EntityManagerInterface $em): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $lot->getId(), $token)) {
            $em->remove($lot);
            $em->flush();
            $this->addFlash('success', 'Lot supprimé.');
        }

        return $this->redirectToRoute('app_lot_index');
    }
}
