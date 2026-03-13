<?php

namespace App\Controller;

use App\Entity\Medicament;
use App\Form\MedicamentType;
use App\Repository\MedicamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacie/medicament')]
final class MedicamentController extends AbstractController
{
    #[Route(name: 'app_medicament_index', methods: ['GET'])]
    public function index(MedicamentRepository $repo): Response
    {
        return $this->render('pharmacie/medicament/index.html.twig', [
            'medicaments' => $repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_medicament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $m = new Medicament();
        $form = $this->createForm(MedicamentType::class, $m);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($m);
            $em->flush();
            $this->addFlash('success', 'Médicament ajouté.');
            return $this->redirectToRoute('app_medicament_index');
        }

        return $this->render('pharmacie/medicament/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_medicament_show', methods: ['GET'])]
    public function show(Medicament $medicament): Response
    {
        return $this->render('pharmacie/medicament/show.html.twig', [
            'medicament' => $medicament,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_medicament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Medicament $medicament, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MedicamentType::class, $medicament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Médicament mis à jour.');
            return $this->redirectToRoute('app_medicament_index');
        }

        return $this->render('pharmacie/medicament/edit.html.twig', [
            'medicament' => $medicament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_medicament_delete', methods: ['POST'])]
    public function delete(Request $request, Medicament $medicament, EntityManagerInterface $em): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $medicament->getId(), $token)) {
            $em->remove($medicament);
            $em->flush();
            $this->addFlash('success', 'Médicament supprimé.');
        }

        return $this->redirectToRoute('app_medicament_index');
    }
}
