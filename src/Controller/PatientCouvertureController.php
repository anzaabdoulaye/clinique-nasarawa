<?php

namespace App\Controller;

use App\Entity\PatientCouverture;
use App\Form\PatientCouvertureType;
use App\Repository\PatientCouvertureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/patient-couvertures')]
class PatientCouvertureController extends AbstractController
{
    #[Route('/', name: 'app_patient_couverture_index', methods: ['GET'])]
    public function index(PatientCouvertureRepository $repository): Response
    {
        return $this->render('patient_couverture/index.html.twig', [
            'couvertures' => $repository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_patient_couverture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, PatientCouvertureRepository $repository): Response
    {
        $couverture = new PatientCouverture();
        $form = $this->createForm(PatientCouvertureType::class, $couverture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->desactiverAutresCouverturesActives($couverture, $repository);

            $em->persist($couverture);
            $em->flush();

            $this->addFlash('success', 'Couverture patient créée avec succès.');
            return $this->redirectToRoute('app_patient_couverture_index');
        }

        return $this->render('patient_couverture/new.html.twig', [
            'form' => $form,
            'couverture' => $couverture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_patient_couverture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PatientCouverture $couverture, EntityManagerInterface $em, PatientCouvertureRepository $repository): Response
    {
        $form = $this->createForm(PatientCouvertureType::class, $couverture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->desactiverAutresCouverturesActives($couverture, $repository);

            $em->flush();

            $this->addFlash('success', 'Couverture patient modifiée avec succès.');
            return $this->redirectToRoute('app_patient_couverture_index');
        }

        return $this->render('patient_couverture/edit.html.twig', [
            'form' => $form,
            'couverture' => $couverture,
        ]);
    }

    private function desactiverAutresCouverturesActives(
        PatientCouverture $couverture,
        PatientCouvertureRepository $repository
    ): void {
        if (!$couverture->isActif() || !$couverture->getPatient()) {
            return;
        }

        $autres = $repository->findBy([
            'patient' => $couverture->getPatient(),
            'actif' => true,
        ]);

        foreach ($autres as $autre) {
            if ($autre->getId() !== $couverture->getId()) {
                $autre->setActif(false);
            }
        }
    }
}