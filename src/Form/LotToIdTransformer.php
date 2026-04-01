<?php

namespace App\Form;

use App\Entity\Lot;
use App\Repository\LotRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class LotToIdTransformer implements DataTransformerInterface
{
    public function __construct(private readonly LotRepository $lotRepository)
    {
    }

    public function transform(mixed $value): string
    {
        if (!$value instanceof Lot) {
            return '';
        }

        return (string) $value->getId();
    }

    public function reverseTransform(mixed $value): ?Lot
    {
        if ($value === null || $value === '') {
            return null;
        }

        $lot = $this->lotRepository->find((int) $value);
        if (!$lot instanceof Lot) {
            throw new TransformationFailedException('Le lot sélectionné est introuvable.');
        }

        return $lot;
    }
}
