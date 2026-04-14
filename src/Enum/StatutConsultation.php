<?php
namespace App\Enum;

enum StatutConsultation: string
{
    case BROUILLON = 'brouillon';
    case EN_COURS = 'en_cours';
    case CLOTURE = 'cloture';
    case ANNULE = 'annule';
}