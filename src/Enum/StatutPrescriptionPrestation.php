<?php

namespace App\Enum;

enum StatutPrescriptionPrestation: string
{
    case PRESCRIT = 'prescrit';
    case FACTURE = 'facture';
    case PAYE = 'paye';
    case EN_COURS = 'en_cours';
    case REALISE = 'realise';
    case ANNULE = 'annule';
}