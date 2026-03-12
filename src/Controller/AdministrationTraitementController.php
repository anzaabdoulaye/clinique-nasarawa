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

        if ($administration) {
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
            ->setStatut('administre')
            ->setAdministreLe(new \DateTimeImmutable());

        $em->persist($administration);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'checked' => true
        ]);
    }
}