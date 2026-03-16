<?php

namespace App\Enum;

enum StatutFacture: string
{
    case BROUILLON = 'brouillon';
    case NON_PAYE = 'non_paye';
    case PARTIELLEMENT_PAYE = 'partiellement_paye';
    case PAYE = 'paye';
    case ANNULE = 'annule';
}