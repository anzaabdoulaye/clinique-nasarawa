<?php

namespace App\Controller;

use App\Entity\ConventionPriseEnCharge;
use App\Form\ConventionPriseEnChargeType;
use App\Repository\ConventionPriseEnChargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/conventions-pec')]
class ConventionPriseEnChargeController extends AbstractController
{
    #[Route('/', name: 'app_convention_pec_index', methods: ['GET'])]
    public function index(ConventionPriseEnChargeRepository $repository): Response
    {
        return $this->render('convention_pec/index.html.twig', [
            'conventions' => $repository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_convention_pec_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $convention = new ConventionPriseEnCharge();
        $form = $this->createForm(ConventionPriseEnChargeType::class, $convention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($convention);
            $em->flush();

            $this->addFlash('success', 'Convention créée avec succès.');
            return $this->redirectToRoute('app_convention_pec_index');
        }

        return $this->render('convention_pec/new.html.twig', [
            'form' => $form,
            'convention' => $convention,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_convention_pec_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConventionPriseEnCharge $convention, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ConventionPriseEnChargeType::class, $convention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Convention modifiée avec succès.');
            return $this->redirectToRoute('app_convention_pec_index');
        }

        return $this->render('convention_pec/edit.html.twig', [
            'form' => $form,
            'convention' => $convention,
        ]);
    }
}