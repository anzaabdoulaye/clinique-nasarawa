<?php

namespace App\Form;

use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\VenteLigne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenteLigneType extends AbstractType
{
    public function __construct(private readonly MedicamentToIdTransformer $medicamentToIdTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ligne = $builder->getData();
        $medicament = $ligne instanceof VenteLigne ? $ligne->getMedicament() : null;
        $medicamentLabel = null;

        if ($medicament instanceof Medicament) {
            $medicamentLabel = $medicament->getNom();

            if ($medicament->getCodeBarre()) {
                $medicamentLabel .= ' | ' . $medicament->getCodeBarre();
            }
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
                    'data-initial-price' => $medicament ? (string) $medicament->getPrixUnitaire() : '',
                    'data-initial-id' => $medicament ? (string) $medicament->getId() : '',
                ],
            ])
            ->add('medicament', HiddenType::class, [
                'attr' => [
                    'class' => 'medicament-id-input',
                ],
            ])
            ->add('lot', EntityType::class, [
                'class' => Lot::class,
                'choice_label' => function (Lot $l) {
                    $parts = [$l->getNumeroLot() ?: '—Lot—'];

                    if ($l->getDatePeremption()) {
                        $parts[] = $l->getDatePeremption()->format('d/m/Y');
                    }

                    $parts[] = 'Qte: ' . $l->getQuantite();

                    return implode(' | ', $parts);
                },
                'required' => false,
                'placeholder' => '— Optionnel (choisir lot) —',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('quantite', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                ],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'scale' => 2,
                'disabled' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);

            $builder->get('medicament')->addModelTransformer($this->medicamentToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VenteLigne::class,
        ]);
    }
}