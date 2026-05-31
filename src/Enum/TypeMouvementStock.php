<?php

namespace App\Enum;

enum TypeMouvementStock: string
{
    case ENTREE_ACHAT = 'entree_achat'; // Livraison fournisseur
    case ENTREE_RETOUR = 'entree_retour'; // Un service ramène un produit non utilisé
    
    case SORTIE_PATIENT = 'sortie_patient'; // Consommé pour un patient (Facturation)
    case SORTIE_SERVICE = 'sortie_service'; // Dotation à un service (ex: Urgences)
    case SORTIE_PERTE = 'sortie_perte'; // Péremption, casse, vol
    case SORTIE_AJUSTEMENT = 'sortie_ajustement'; // Correction suite à un inventaire
}