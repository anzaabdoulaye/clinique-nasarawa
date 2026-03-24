<?php

namespace App\Enum;

enum MotifMouvement: string
{
    case ACHAT = 'ACHAT';
    case DON = 'DON';
    case INVENTAIRE = 'INVENTAIRE';
    case STOCK_INITIAL = 'STOCK_INITIAL';
    case VENTE = 'VENTE';
    case PEREMPTION = 'PEREMPTION';
    case CASSE = 'CASSE';
    case PERTE = 'PERTE';
    case DESTRUCTION = 'DESTRUCTION';
    case REPARATION = 'REPARATION';
    case PRET = 'PRET';
    case RETOUR = 'RETOUR';
    case AJUSTEMENT = 'AJUSTEMENT';
    case TRANSFERT = 'TRANSFERT';

    public function label(): string
    {
        return match ($this) {
            self::ACHAT => 'Achat',
            self::DON => 'Don',
            self::INVENTAIRE => 'Inventaire',
            self::STOCK_INITIAL => 'Stock initial',
            self::VENTE => 'Vente',
            self::PEREMPTION => 'Péremption',
            self::CASSE => 'Casse',
            self::PERTE => 'Perte',
            self::DESTRUCTION => 'Destruction',
            self::REPARATION => 'Réparation',
            self::PRET => 'Prêt',
            self::RETOUR => 'Retour',
            self::AJUSTEMENT => 'Ajustement',
            self::TRANSFERT => 'Transfert',
        };
    }
}