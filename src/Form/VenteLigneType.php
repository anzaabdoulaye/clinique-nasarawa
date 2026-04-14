<?php

namespace App\Form;

use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\VenteLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenteLigneType extends AbstractType
{
    public function __construct(
        private readonly MedicamentToIdTransformer $medicamentToIdTransformer,
        private readonly LotToIdTransformer $lotToIdTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ligne = $builder->getData();
        $medicament = $ligne instanceof VenteLigne ? $ligne->getMedicament() : null;
        $lot = $ligne instanceof VenteLigne ? $ligne->getLot() : null;
        $initialPrice = $ligne instanceof VenteLigne ? $ligne->getPrixUnitaire() : null;
        $medicamentLabel = null;

        if ($medicament instanceof Medicament) {
            $medicamentLabel = $medicament->getNom();

            if ($medicament->getCodeBarre()) {
                $medicamentLabel .= ' | ' . $medicament->getCodeBarre();
            }
        }

        $lotLabel = '';
        if ($lot instanceof Lot) {
            $parts = [$lot->getNumeroLot() ?: 'Lot #' . $lot->getId()];
            if ($lot->getDatePeremption()) {
                $parts[] = 'Exp: ' . $lot->getDatePeremption()->format('d/m/Y');
            }
            $parts[] = 'Qté: ' . $lot->getQuantite();
            $lotLabel = implode(' | ', $parts);
        }

        $builder
            ->add('medicamentSearch', TextType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $medicamentLabel,
                'attr' => [
                    'class' => 'form-control medicament-search-input',
                    'placeholder' => 'Rechercher par nom ou code-barres...',
                    'autocomplete' => 'off',
                    'data-search-url' => '/caisse/medicament/search',
                    'data-initial-label' => $medicamentLabel ?? '',
                    'data-initial-price' => $initialPrice !== null ? (string) $initialPrice : '',
                    'data-initial-id' => $medicament ? (string) $medicament->getId() : '',
                    'data-initial-lot-id' => $lot ? (string) $lot->getId() : '',
                    'data-initial-lot-label' => $lotLabel,
                ],
            ])
            ->add('medicament', HiddenType::class, [
                'attr' => [
                    'class' => 'medicament-id-input',
                ],
            ])
            ->add('lot', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'lot-id-input',
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                ],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01',
                ],
            ]);

        $builder->get('medicament')->addModelTransformer($this->medicamentToIdTransformer);
        $builder->get('lot')->addModelTransformer($this->lotToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VenteLigne::class,
        ]);
    }
}