<?php

namespace App\Controller;

use App\Repository\AntibiotiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/laboratoire')]
#[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"))]
class ApiLaboratoireController extends AbstractController
{
    #[Route('/antibiotiques/actifs', name: 'api_labo_antibiotiques_actifs', methods: ['GET'])]
    public function getAntibiotiquesActifs(AntibiotiqueRepository $repo): JsonResponse
    {
        $antibiotiques = $repo->findBy(['estActif' => true], ['famille' => 'ASC', 'nom' => 'ASC']);
        
        $data = [];
        foreach ($antibiotiques as $atb) {
            $data[] = [
                'id' => $atb->getId(),
                'nom' => $atb->getNom(),
                'famille' => $atb->getFamille() ?? 'Général',
            ];
        }

        return $this->json($data);
    }
}