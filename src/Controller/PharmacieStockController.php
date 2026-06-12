<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\MouvementStock;
use App\Enum\TypeMouvementStock;
use App\Form\EntreeStockType;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pharmacie/stock')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class PharmacieStockController extends AbstractController
{
    #[Route('/entree', name: 'app_pharmacie_stock_entree', methods: ['GET', 'POST'])]
    public function entree(Request $request, EntityManagerInterface $em, LotRepository $lotRepo): Response
    {
        $form = $this->createForm(EntreeStockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $medicament = $data['medicament'];
            $numeroLot = $data['numeroLot'] ?: 'SANS-LOT'; // Fallback si pas de lot
            $datePeremption = $data['datePeremption'];
            $quantite = $data['quantite'];
            $prixAchat = $data['prixAchat'];

            // 1. Chercher si ce lot existe déjà pour ce produit précis
            $lot = $lotRepo->findOneBy([
                'medicament' => $medicament,
                'numeroLot' => $numeroLot
            ]);

            if (!$lot) {
                // Si le lot n'existe pas, on le crée
                $lot = new Lot();
                $lot->setMedicament($medicament);
                $lot->setNumeroLot($numeroLot);
                $lot->setDatePeremption($datePeremption);
                $em->persist($lot);
            } else {
                // Si on rajoute du stock à un lot existant, on met à jour la péremption au cas où
                $lot->setDatePeremption($datePeremption);
            }

            // 2. Mettre à jour le Lot (Quantité globale + Dernier prix d'achat)
            $nouveauStock = $lot->getQuantite() + $quantite;
            $lot->setQuantite($lot->getQuantite() + $quantite);
            $lot->setPrixAchat($prixAchat);

            // 3. Créer la traçabilité absolue (Mouvement de Stock)
            $mouvement = new MouvementStock();
            $mouvement->setMedicament($medicament);
            $mouvement->setLot($lot);
            $mouvement->setType(TypeMouvementStock::ENTREE_ACHAT);
            $mouvement->setQuantite($quantite); // Quantité positive = Entrée
            $mouvement->setStockApresMouvement($nouveauStock);
            $mouvement->setValeurAchatUnitaire($prixAchat);
            
            // Récupération de l'utilisateur connecté
            $userLabel = method_exists($this->getUser(), 'getNomComplet') 
                ? $this->getUser()->getNomComplet() 
                : $this->getUser()?->getUserIdentifier();
            $mouvement->setOperateur($userLabel ?? 'Système');
            
            $mouvement->setMotif('Approvisionnement pharmacie');

            $em->persist($mouvement);
            
            // 4. Sauvegarde globale
            $em->flush();

            $this->addFlash('success', sprintf('Entrée validée : %d unités de "%s" ajoutées au stock (Lot: %s).', $quantite, $medicament->getNom(), $numeroLot));

            // On redirige vers le même formulaire pour enchaîner les saisies rapidement
            return $this->redirectToRoute('app_pharmacie_stock_entree');
        }

        return $this->render('pharmacie/stock/entree.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sortie', name: 'app_pharmacie_stock_sortie', methods: ['GET', 'POST'])]
    public function sortie(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(\App\Form\SortieStockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Lot $lot */
            $lot = $data['lot'];
            $quantiteSortie = $data['quantite'];

            if ($quantiteSortie > $lot->getQuantite()) {
                $this->addFlash('danger', 'Erreur : Vous essayez de sortir une quantité supérieure au stock disponible dans ce lot.');
                return $this->redirectToRoute('app_pharmacie_stock_sortie');
            }

            // 1. Déduction du stock
            $nouveauStock = $lot->getQuantite() - $quantiteSortie;
            $lot->setQuantite($lot->getQuantite() - $quantiteSortie);

            // 2. Traçabilité (Mouvement)
            $mouvement = new MouvementStock();
            $mouvement->setMedicament($lot->getMedicament());
            $mouvement->setLot($lot);
            $mouvement->setType($data['type']); // SORTIE_PERTE, SORTIE_SERVICE, etc.
            $mouvement->setQuantite(-$quantiteSortie); // ⚠️ Attention : Quantité NÉGATIVE
            $mouvement->setStockApresMouvement($nouveauStock);
            $mouvement->setValeurAchatUnitaire($lot->getPrixAchat());
            $mouvement->setMotif($data['motif']);
            
            $userLabel = method_exists($this->getUser(), 'getNomComplet') ? $this->getUser()->getNomComplet() : $this->getUser()?->getUserIdentifier();
            $mouvement->setOperateur($userLabel ?? 'Système');

            $em->persist($mouvement);
            $em->flush();

            $this->addFlash('success', sprintf('Sortie validée : %d unités retirées du lot %s.', $quantiteSortie, $lot->getNumeroLot()));
            return $this->redirectToRoute('app_pharmacie_stock_sortie');
        }

        return $this->render('pharmacie/stock/sortie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ajustement', name: 'app_pharmacie_stock_ajustement', methods: ['GET', 'POST'])]
    //#[IsGranted('ROLE_ADMIN')] 
    public function ajustement(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(\App\Form\AjustementStockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            /** @var \App\Entity\Lot $lot */
            $lot = $data['lot'];
            $quantiteReelle = (int) $data['quantiteReelle'];
            $motif = $data['motif'];
            
            $stockInformatiqueActuel = $lot->getQuantite();
            
            // 1. Calcul de la différence (L'écart d'inventaire)
            $ecart = $quantiteReelle - $stockInformatiqueActuel;

            if ($ecart === 0) {
                $this->addFlash('warning', 'La quantité saisie est identique au stock actuel. Aucun ajustement n\'a été fait.');
                return $this->redirectToRoute('app_pharmacie_stock_ajustement');
            }

            // 2. Mise à jour immédiate du Lot
            $lot->setQuantite($quantiteReelle);

            // 3. Traçabilité (Création du mouvement d'Ajustement)
            $mouvement = new \App\Entity\MouvementStock();
            $mouvement->setMedicament($lot->getMedicament());
            $mouvement->setLot($lot);
            $mouvement->setType(\App\Enum\TypeMouvementStock::AJUSTEMENT_INVENTAIRE);
            
            // L'écart peut être positif (oubli de saisie) ou négatif (perte/vol/casse)
            $mouvement->setQuantite($ecart); 
            $mouvement->setStockApresMouvement($quantiteReelle);
            $mouvement->setValeurAchatUnitaire($lot->getPrixAchat());
            $mouvement->setMotif('RÉGULARISATION : ' . $motif);
            
            $userLabel = method_exists($this->getUser(), 'getNomComplet') ? $this->getUser()->getNomComplet() : $this->getUser()?->getUserIdentifier();
            $mouvement->setOperateur($userLabel);

            $em->persist($mouvement);
            $em->flush();

            $signe = $ecart > 0 ? '+' : '';
            $this->addFlash('success', sprintf('Ajustement validé ! Le stock informatique a été corrigé (Écart de %s%d unités).', $signe, $ecart));

            return $this->redirectToRoute('app_pharmacie_stock_ajustement');
        }

        return $this->render('pharmacie/stock/ajustement.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}