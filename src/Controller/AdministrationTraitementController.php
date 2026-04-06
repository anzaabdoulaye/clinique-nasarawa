<?php

namespace App\Controller;

use App\Entity\AdministrationTraitement;
use App\Entity\TraitementHospitalisation;
use App\Repository\AdministrationTraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration-traitement')]
final class AdministrationTraitementController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'app_administration_traitement_toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        TraitementHospitalisation $traitement,
        AdministrationTraitementRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $date = $request->request->get('date');
        $heure = $request->request->get('heure');
        $observation = trim((string) $request->request->get('observation', ''));

        if (!$date || $heure === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Date ou heure manquante.'
            ], 400);
        }

        try {
            $dateObj = new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Date invalide.'
            ], 400);
        }

        $heure = (int) $heure;

        if (!$traitement->isScheduledAtHour($heure)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cette heure ne fait pas partie du traitement.'
            ], 400);
        }

        $administration = $repo->findOneForTraitementDateHeure($traitement, $dateObj, $heure);

        if (!$administration && !$traitement->isWithinPeriodAt($dateObj, $heure)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cette période de traitement n\'est pas encore disponible.'
            ], 400);
        }

        $isLateAdministration = !$administration && $traitement->isLateSlotAt($dateObj, $heure);

        if ($isLateAdministration && $observation === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une explication est obligatoire pour enregistrer un traitement en retard.'
            ], 400);
        }

        if ($administration) {
            if (!$this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Seul un compte medecin ou admin peut retirer un traitement administre.'
                ], 403);
            }

            $em->remove($administration);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'checked' => false
            ]);
        }

        $administration = new AdministrationTraitement();
        $administration
            ->setTraitement($traitement)
            ->setDateAdministration($dateObj)
            ->setHeure($heure)
            ->setStatut($isLateAdministration ? 'retard' : 'administre')
            ->setAdministreLe(new \DateTimeImmutable())
            ->setObservation($observation !== '' ? $observation : null);

        $em->persist($administration);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'checked' => true,
            'late' => $isLateAdministration
        ]);
    }
}