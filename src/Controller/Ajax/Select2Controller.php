<?php

namespace App\Controller\Ajax;

use App\Repository\DossierMedicalRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ajax/select2')]
final class Select2Controller extends AbstractController
{
    #[Route('/medecins', name: 'app_ajax_select2_medecins', methods: ['GET'])]
    public function medecins(Request $request, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $utilisateurRepository->createQueryBuilder('u')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->setMaxResults(20);

        if ($term !== '') {
            $qb->andWhere('u.nom LIKE :term OR u.prenom LIKE :term OR CONCAT(u.nom, \' \', u.prenom) LIKE :term OR CONCAT(u.prenom, \' \', u.nom) LIKE :term')
               ->setParameter('term', '%' . $term . '%');
        }

        $utilisateurs = array_filter(
            $qb->getQuery()->getResult(),
            static fn($u) => in_array('ROLE_MEDECIN', $u->getRoles(), true)
        );

        $results = array_map(static function ($u) {
            return [
                'id' => $u->getId(),
                'text' => $u->getNomComplet(),
            ];
        }, $utilisateurs);

        return new JsonResponse([
            'results' => array_values($results),
        ]);
    }

    #[Route('/dossiers-medicaux', name: 'app_ajax_select2_dossiers_medicaux', methods: ['GET'])]
    public function dossiersMedicaux(Request $request, DossierMedicalRepository $dossierMedicalRepository): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $dossierMedicalRepository->createQueryBuilder('d')
            ->leftJoin('d.patient', 'p')
            ->addSelect('p')
            ->orderBy('d.createdAt', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->setMaxResults(20);

        if ($term !== '') {
            $qb->andWhere(
                'd.numeroDossier LIKE :term
                 OR p.code LIKE :term
                 OR p.nom LIKE :term
                 OR p.prenom LIKE :term
                 OR p.telephone LIKE :term'
            )
            ->setParameter('term', '%' . $term . '%');
        }

        $results = array_map(static function ($dossier) {
            $patient = method_exists($dossier, 'getPatient') ? $dossier->getPatient() : null;

            $patientText = $patient
                ? trim(($patient->getCode() ?? '') . ' - ' . ($patient->getNom() ?? '') . ' ' . ($patient->getPrenom() ?? '') . ' - ' . ($patient->getTelephone() ?? ''))
                : 'Sans patient';

            return [
                'id' => $dossier->getId(),
                'text' => ($dossier->getNumeroDossier() ?: ('#' . $dossier->getId())) . ' / ' . $patientText,
            ];
        }, $qb->getQuery()->getResult());

        return new JsonResponse([
            'results' => array_values($results),
        ]);
    }
}