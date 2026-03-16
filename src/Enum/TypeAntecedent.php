<?php
// src/Domain/Hospitalisation/Enum/TypeAntecedent.php
namespace App\Enum;

enum TypeAntecedent: string
{
    case MEDICAL = 'medical';
    case CHIRURGICAL = 'chirurgical';
    case FAMILIAL = 'familial';
    case GYNECO = 'gyneco';
    case PERSONNEL = 'personnel';
}
