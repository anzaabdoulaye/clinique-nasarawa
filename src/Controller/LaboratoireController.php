<?php

namespace App\Controller;

use App\Entity\PrescriptionPrestation;
use App\Enum\StatutPrescriptionPrestation;
use App\Repository\PrescriptionPrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/laboratoire')]
final class LaboratoireController extends AbstractController
{
    #[Route('', name: 'app_laboratoire_index', methods: ['GET'])]
    public function index(PrescriptionPrestationRepository $repository): Response
    {
        $aTraiter = $repository->findExamensLaboAPrendreEnCharge();
        $enCours = $repository->findExamensLaboEnCours();
        $realises = $repository->findExamensLaboRealises();

        return $this->render('laboratoire/index.html.twig', [
            'aTraiter' => $aTraiter,
            'enCours' => $enCours,
            'realises' => $realises,
        ]);
    }

    #[Route('/prestation/{id}', name: 'app_laboratoire_show', methods: ['GET'])]
    public function show(PrescriptionPrestation $prestation): Response
    {
        $this->verifierDestinationLaboratoire($prestation);

        return $this->render('laboratoire/show.html.twig', [
            'prestation' => $prestation,
        ]);
    }

    #[Route('/prestation/{id}/prendre-en-charge', name: 'app_laboratoire_prendre_en_charge', methods: ['POST'])]
    public function prendreEnCharge(
        PrescriptionPrestation $prestation,
        EntityManagerInterface $em
    ): Response {
        $this->verifierDestinationLaboratoire($prestation);

        if ($prestation->getStatut() === StatutPrescriptionPrestation::PAYE) {
            $prestation->setStatut(StatutPrescriptionPrestation::EN_COURS);
            $em->flush();
        }

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    #[Route('/prestation/{id}/realiser', name: 'app_laboratoire_realiser', methods: ['POST'])]
    public function realiser(
        PrescriptionPrestation $prestation,
        EntityManagerInterface $em
    ): Response {
        $this->verifierDestinationLaboratoire($prestation);

        if (\in_array($prestation->getStatut(), [
            StatutPrescriptionPrestation::PAYE,
            StatutPrescriptionPrestation::EN_COURS,
        ], true)) {
            $prestation->setStatut(StatutPrescriptionPrestation::REALISE);
            $em->flush();
        }

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    private function verifierDestinationLaboratoire(PrescriptionPrestation $prestation): void
    {
        $service = $prestation->getTarifPrestation()?->getServiceExecution();

        if ($service !== 'laboratoire') {
            throw $this->createNotFoundException('Cette prestation ne relève pas du laboratoire.');
        }
    }
}