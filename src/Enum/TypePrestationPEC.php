<?php

namespace App\Enum;

enum TypePrestationPEC: string
{
    case CONSULTATION = 'CONSULTATION';
    case EXAMEN = 'EXAMEN';
    case HOSPITALISATION = 'HOSPITALISATION';
    case ACTE = 'ACTE';
    case SOIN = 'SOIN';
    case CONSOMMABLE = 'CONSOMMABLE';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTATION => 'Consultation',
            self::EXAMEN => 'Examen',
            self::HOSPITALISATION => 'Hospitalisation',
            self::ACTE => 'Acte',
            self::SOIN => 'Soin',
            self::CONSOMMABLE => 'Consommable',
            self::AUTRE => 'Autre',
        };
    }
}