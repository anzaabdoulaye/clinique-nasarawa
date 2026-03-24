<?php

namespace App\Form;

use App\Entity\Medicament;
use App\Repository\MedicamentRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MedicamentToIdTransformer implements DataTransformerInterface
{
    public function __construct(private readonly MedicamentRepository $medicamentRepository)
    {
    }

    public function transform(mixed $value): string
    {
        if (!$value instanceof Medicament) {
            return '';
        }

        return (string) $value->getId();
    }

    public function reverseTransform(mixed $value): ?Medicament
    {
        if ($value === null || $value === '') {
            return null;
        }

        $medicament = $this->medicamentRepository->find((int) $value);
        if (!$medicament instanceof Medicament) {
            throw new TransformationFailedException('Le medicament selectionne est introuvable.');
        }

        return $medicament;
    }
}