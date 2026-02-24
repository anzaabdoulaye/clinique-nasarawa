<?php
// src/Domain/Core/Enum/StatutRendezVous.php
namespace App\Enum;

enum StatutRendezVous: string
{
    case EN_ATTENTE = 'en_attente';
    case PLANIFIE = 'planifie';
    case CONFIRME = 'confirme';
    case ANNULE = 'annule';
    case TERMINE = 'termine';
}