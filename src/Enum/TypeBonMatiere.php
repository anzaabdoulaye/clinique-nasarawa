<?php

namespace App\Enum;

enum TypeBonMatiere: string
{
    case ENTREE = 'ENTREE';
    case SORTIE_DEFINITIVE = 'SORTIE_DEFINITIVE';
    case SORTIE_PROVISOIRE = 'SORTIE_PROVISOIRE';

    public function label(): string
    {
        return match ($this) {
            self::ENTREE => 'Bon d’entrée',
            self::SORTIE_DEFINITIVE => 'Bon de sortie définitive',
            self::SORTIE_PROVISOIRE => 'Bon de sortie provisoire',
        };
    }
}