<?php

namespace App\Enum;

enum CategorieTarif: string
{
    case CONSULTATION = 'consultation';
    case EXAMEN_BIOLOGIQUE = 'examen_biologique';
    case IMAGERIE = 'imagerie';
    case EXAMEN_FONCTIONNEL = 'examen_fonctionnel';
    case HOSPITALISATION = 'hospitalisation';
    case ACTE = 'acte';
    case CONSOMMABLE = 'consommable';
}