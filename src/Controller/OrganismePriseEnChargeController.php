<?php

namespace App\Controller;

use App\Entity\OrganismePriseEnCharge;
use App\Form\OrganismePriseEnChargeType;
use App\Repository\OrganismePriseEnChargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/organismes-pec')]
class OrganismePriseEnChargeController extends AbstractController
{
    #[Route('/', name: 'app_organisme_pec_index', methods: ['GET'])]
    public function index(OrganismePriseEnChargeRepository $repository): Response
    {
        return $this->render('organisme_pec/index.html.twig', [
            'organismes' => $repository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_organisme_pec_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $organisme = new OrganismePriseEnCharge();
        $form = $this->createForm(OrganismePriseEnChargeType::class, $organisme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($organisme);
            $em->flush();

            $this->addFlash('success', 'Organisme créé avec succès.');
            return $this->redirectToRoute('app_organisme_pec_index');
        }

        return $this->render('organisme_pec/new.html.twig', [
            'form' => $form,
            'organisme' => $organisme,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_organisme_pec_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OrganismePriseEnCharge $organisme, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OrganismePriseEnChargeType::class, $organisme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Organisme modifié avec succès.');
            return $this->redirectToRoute('app_organisme_pec_index');
        }

        return $this->render('organisme_pec/edit.html.twig', [
            'form' => $form,
            'organisme' => $organisme,
        ]);
    }
}