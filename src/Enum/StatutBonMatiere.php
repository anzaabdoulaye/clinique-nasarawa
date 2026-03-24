<?php

namespace App\Enum;

enum StatutBonMatiere: string
{
    case BROUILLON = 'BROUILLON';
    case VALIDE = 'VALIDE';
    case ANNULE = 'ANNULE';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::VALIDE => 'Validé',
            self::ANNULE => 'Annulé',
        };
    }
}