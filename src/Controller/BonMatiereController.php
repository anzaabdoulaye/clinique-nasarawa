<?php

namespace App\Controller;

use App\Entity\BonMatiere;
use App\Entity\BonMatiereLigne;
use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\Utilisateur;
use App\Entity\Medecin;
use App\Entity\HonoraireMedecin;
use App\Entity\JournalCaisse;
use App\Enum\MotifMouvement;
use App\Enum\TypeBonMatiere;
use App\Form\MedecinType;
use App\Form\HonoraireMedecinType;
use App\Form\JournalCaisseType;
use App\Repository\BonMatiereRepository;
use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MedecinRepository;
use App\Repository\HonoraireMedecinRepository;
use App\Repository\JournalCaisseRepository;
use App\Service\ComptabiliteMatiereService;
use App\Service\PharmacyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/comptabilite-matiere')]
final class BonMatiereController extends AbstractController
{
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"))]
    #[Route('', name: 'app_comptabilite_matiere_index', methods: ['GET'])]
    public function index(
        Request $request,
        BonMatiereRepository $bonMatiereRepository,
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository,
        MedecinRepository $medecinRepository,
        HonoraireMedecinRepository $honoraireRepository,
        JournalCaisseRepository $caisseRepository
    ): Response {
        $periodeFilter = $request->query->get('periode', 'recent');
        $bons = $bonMatiereRepository->findBy([], ['dateBon' => 'DESC', 'id' => 'DESC']);

        // ... (Votre logique de filtrage des bons reste inchangée ici) ...
        if ($periodeFilter === 'recent') {
            $date = new \DateTimeImmutable('-30 days');
            $bons = array_filter($bons, fn($bon) => $bon->getDateBon() && $bon->getDateBon() >= $date);
        } elseif ($periodeFilter === 'month') {
            $now = new \DateTimeImmutable();
            $startOfMonth = $now->setDate((int)$now->format('Y'), (int)$now->format('m'), 1)->setTime(0, 0, 0);
            $bons = array_filter($bons, fn($bon) => $bon->getDateBon() && $bon->getDateBon() >= $startOfMonth);
        } elseif ($periodeFilter === 'quarter') {
            $date = new \DateTimeImmutable('-90 days');
            $bons = array_filter($bons, fn($bon) => $bon->getDateBon() && $bon->getDateBon() >= $date);
        } elseif ($periodeFilter === 'year') {
            $date = new \DateTimeImmutable('-365 days');
            $bons = array_filter($bons, fn($bon) => $bon->getDateBon() && $bon->getDateBon() >= $date);
        }

        // --- PRÉPARATION DES FORMULAIRES POUR LES MODALES ---
        $medecinForm = $this->createForm(MedecinType::class, new Medecin());
        $honoraireForm = $this->createForm(HonoraireMedecinType::class, new HonoraireMedecin());
        $caisseForm = $this->createForm(JournalCaisseType::class, new JournalCaisse());

        return $this->render('comptabilite_matiere/index.html.twig', [
            'bons' => $bons,
            'types' => TypeBonMatiere::cases(),
            'motifs' => MotifMouvement::cases(),
            'medicaments' => $medicamentRepository->findBy(['actif' => true], ['nom' => 'ASC']),
            'lots' => $lotRepository->findBy([], ['id' => 'DESC']),
            'periodeFilter' => $periodeFilter,
            
            // Nouvelles variables pour le Hub
            'medecins' => $medecinRepository->findAll(),
            'honoraires' => $honoraireRepository->findAll(),
            'journalCaisses' => $caisseRepository->findAll(),
            'medecinForm' => $medecinForm->createView(),
            'honoraireForm' => $honoraireForm->createView(),
            'caisseForm' => $caisseForm->createView(),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"
))]
    #[Route('/dashboard', name: 'app_comptabilite_matiere_dashboard', methods: ['GET'])]
    public function dashboard(
        BonMatiereRepository $bonMatiereRepository,
        PharmacyService $pharmacyService,
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository
    ): Response {
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = $todayStart->setTime(23, 59, 59);

        $bons = $bonMatiereRepository->findBy([], ['dateBon' => 'DESC', 'id' => 'DESC']);
        $bonsToday = array_filter($bons, static function (BonMatiere $bon) use ($todayStart, $todayEnd) {
            $date = $bon->getDateBon();
            return $date >= $todayStart && $date <= $todayEnd;
        });

        $entreesToday = 0;
        $sortiesDefToday = 0;
        $sortiesProvToday = 0;

        foreach ($bonsToday as $bon) {
            if ($bon->getType() === TypeBonMatiere::ENTREE) {
                $entreesToday++;
            } elseif ($bon->getType() === TypeBonMatiere::SORTIE_DEFINITIVE) {
                $sortiesDefToday++;
            } elseif ($bon->getType() === TypeBonMatiere::SORTIE_PROVISOIRE) {
                $sortiesProvToday++;
            }
        }

        return $this->render('comptabilite_matiere/dashboard.html.twig', [
            'bons' => $bons,
            'bonsTodayCount' => count($bonsToday),
            'entreesTodayCount' => $entreesToday,
            'sortiesDefTodayCount' => $sortiesDefToday,
            'sortiesProvTodayCount' => $sortiesProvToday,
            'lotsNearExpiration' => $pharmacyService->getLotsNearExpiration(30),
            'lowStockMedicaments' => $pharmacyService->getMedicamentsLowStock(10),
            'recentBons' => array_slice($bons, 0, 10),
            'types' => TypeBonMatiere::cases(),
            'motifs' => MotifMouvement::cases(),
            'medicaments' => $medicamentRepository->findBy(['actif' => true], ['nom' => 'ASC']),
            'lots' => $lotRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"
))]
    #[Route('/new', name: 'app_comptabilite_matiere_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository,
        ComptabiliteMatiereService $comptabiliteMatiereService
    ): Response {
        $medicaments = $medicamentRepository->findBy(['actif' => true], ['nom' => 'ASC']);
        $lots = $lotRepository->findBy([], ['id' => 'DESC']);

        if ($request->isMethod('POST')) {
            try {
                /** @var Utilisateur|null $user */
                $user = $this->getUser();

                if (!$user instanceof Utilisateur) {
                    throw new \RuntimeException('Utilisateur non authentifié.');
                }

                $typeValue = (string) $request->request->get('type');
                $motifValue = (string) $request->request->get('motif');
                $reference = trim((string) $request->request->get('reference'));
                $observation = trim((string) $request->request->get('observation'));

                $rawLignes = $request->request->all('lignes');

                if (empty($typeValue)) {
                    throw new \RuntimeException('Le type de bon est obligatoire.');
                }

                if (empty($motifValue)) {
                    throw new \RuntimeException('Le motif est obligatoire.');
                }

                if (empty($rawLignes)) {
                    throw new \RuntimeException('Veuillez ajouter au moins une ligne.');
                }

                $type = TypeBonMatiere::from($typeValue);
                $motif = MotifMouvement::from($motifValue);

                $lignesData = [];

                foreach ($rawLignes as $index => $rawLigne) {
                    $medicamentId = isset($rawLigne['medicament']) ? (int) $rawLigne['medicament'] : 0;
                    $lotId = isset($rawLigne['lot']) && $rawLigne['lot'] !== '' ? (int) $rawLigne['lot'] : null;
                    $numeroLot = trim((string) ($rawLigne['numeroLot'] ?? ''));
                    $datePeremptionValue = trim((string) ($rawLigne['datePeremption'] ?? ''));
                    $quantite = isset($rawLigne['quantite']) ? (int) $rawLigne['quantite'] : 0;
                    $prixUnitaire = isset($rawLigne['prixUnitaire']) && $rawLigne['prixUnitaire'] !== ''
                        ? (float) $rawLigne['prixUnitaire']
                        : null;
                    $ligneObservation = trim((string) ($rawLigne['observation'] ?? ''));

                    $medicament = $medicamentRepository->find($medicamentId);
                    if (!$medicament instanceof Medicament) {
                        throw new \RuntimeException(sprintf('Médicament invalide à la ligne %d.', $index + 1));
                    }

                    $lot = null;
                    if ($lotId) {
                        $lot = $lotRepository->find($lotId);
                        if (!$lot instanceof Lot) {
                            throw new \RuntimeException(sprintf('Lot invalide à la ligne %d.', $index + 1));
                        }

                        if ($lot->getMedicament()->getId() !== $medicament->getId()) {
                            throw new \RuntimeException(sprintf(
                                'Le lot sélectionné à la ligne %d ne correspond pas au médicament choisi.',
                                $index + 1
                            ));
                        }
                    }

                    if ($quantite <= 0) {
                        throw new \RuntimeException(sprintf('Quantité invalide à la ligne %d.', $index + 1));
                    }

                    $datePeremption = null;
                    if ($datePeremptionValue !== '') {
                        $datePeremption = \DateTime::createFromFormat('Y-m-d', $datePeremptionValue);

                        if (!$datePeremption instanceof \DateTime) {
                            throw new \RuntimeException(sprintf('Date de péremption invalide à la ligne %d.', $index + 1));
                        }
                    }

                    if ($type !== TypeBonMatiere::ENTREE && !$lot) {
                        throw new \RuntimeException(sprintf(
                            'Le lot est obligatoire à la ligne %d pour ce type de bon.',
                            $index + 1
                        ));
                    }

                    $lignesData[] = [
                        'medicament' => $medicament,
                        'lot' => $lot,
                        'numeroLot' => $numeroLot !== '' ? $numeroLot : null,
                        'datePeremption' => $datePeremption,
                        'quantite' => $quantite,
                        'prixUnitaire' => $prixUnitaire,
                        'observation' => $ligneObservation ?: null,
                    ];
                }

                $bon = match ($type) {
                    TypeBonMatiere::ENTREE => $comptabiliteMatiereService->creerBonEntree(
                        lignesData: $lignesData,
                        motif: $motif,
                        user: $user,
                        reference: $reference ?: null,
                        observation: $observation ?: null,
                        ordonnateur: $user
                    ),
                    TypeBonMatiere::SORTIE_DEFINITIVE => $comptabiliteMatiereService->creerBonSortieDefinitive(
                        lignesData: $lignesData,
                        motif: $motif,
                        user: $user,
                        reference: $reference ?: null,
                        observation: $observation ?: null,
                        ordonnateur: $user
                    ),
                    TypeBonMatiere::SORTIE_PROVISOIRE => $comptabiliteMatiereService->creerBonSortieProvisoire(
                        lignesData: $lignesData,
                        motif: $motif,
                        user: $user,
                        reference: $reference ?: null,
                        observation: $observation ?: null,
                        ordonnateur: $user
                    ),
                };

                $this->addFlash('success', 'Le bon matière a été créé avec succès.');

                return $this->redirectToRoute('app_comptabilite_matiere_show', [
                    'id' => $bon->getId(),
                ]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('comptabilite_matiere/new.html.twig', [
            'medicaments' => $medicaments,
            'lots' => $lots,
            'types' => TypeBonMatiere::cases(),
            'motifs' => MotifMouvement::cases(),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"
))]
    #[Route('/{id}', name: 'app_comptabilite_matiere_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(BonMatiere $bon): Response
    {
        return $this->render('comptabilite_matiere/show.html.twig', [
            'bon' => $bon,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"
))]
    #[Route('/{id}/print', name: 'app_comptabilite_matiere_print', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function print(BonMatiere $bon): Response
    {
        return $this->render('comptabilite_matiere/print.html.twig', [
            'bon' => $bon,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_COMPTA_MATIERE')"
))]
    #[Route('/{id}/valider', name: 'app_comptabilite_matiere_valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(
        Request $request,
        BonMatiere $bon,
        ComptabiliteMatiereService $comptabiliteMatiereService
    ): Response {
        if (!$this->isCsrfTokenValid('valider_bon_' . $bon->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_comptabilite_matiere_show', ['id' => $bon->getId()]);
        }

        try {
            $comptabiliteMatiereService->validerBon($bon);
            $this->addFlash('success', sprintf('Le bon %s a été validé avec succès.', $bon->getNumero()));
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur lors de la validation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_comptabilite_matiere_show', [
            'id' => $bon->getId(),
        ]);
    }

    #[Route('/medecin/new', name: 'app_hub_medecin_new', methods: ['POST'])]
    public function newMedecinModal(Request $request, EntityManagerInterface $em): Response
    {
        $medecin = new Medecin();
        $form = $this->createForm(MedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($medecin);
            $em->flush();
            $this->addFlash('success', 'Médecin ajouté avec succès.');
        } else {
            $this->addFlash('danger', 'Erreur lors de l\'ajout du médecin.');
        }

        return $this->redirectToRoute('app_comptabilite_matiere_index');
    }

    #[Route('/honoraire/new', name: 'app_hub_honoraire_new', methods: ['POST'])]
    public function newHonoraireModal(Request $request, EntityManagerInterface $em): Response
    {
        $honoraire = new HonoraireMedecin();
        $form = $this->createForm(HonoraireMedecinType::class, $honoraire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logique de calcul rapatriée
            $montantBrut = $honoraire->getMontantTotal() * $honoraire->getTauxReversement();
            $honoraire->setMontantBrutMedecin($montantBrut);
            
            $tauxIsb = $honoraire->getMedecin()->getTauxIsbDefaut() ?? 0;
            $montantIsb = $montantBrut * $tauxIsb;
            $honoraire->setMontantIsb($montantIsb);
            
            $honoraire->setMontantNetAPayer($montantBrut - $montantIsb);

            $em->persist($honoraire);
            $em->flush();
            $this->addFlash('success', 'Honoraire enregistré avec succès.');
        } else {
            $this->addFlash('danger', 'Erreur lors de l\'enregistrement de l\'honoraire.');
        }

        return $this->redirectToRoute('app_comptabilite_matiere_index');
    }

    #[Route('/caisse/new', name: 'app_hub_caisse_new', methods: ['POST'])]
    public function newCaisseModal(Request $request, EntityManagerInterface $em): Response
    {
        $caisse = new JournalCaisse();
        $form = $this->createForm(JournalCaisseType::class, $caisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($caisse);
            $em->flush();
            $this->addFlash('success', 'Écriture de caisse ajoutée avec succès.');
        } else {
            $this->addFlash('danger', 'Erreur lors de l\'ajout au journal de caisse.');
        }

        return $this->redirectToRoute('app_comptabilite_matiere_index');
    }
}