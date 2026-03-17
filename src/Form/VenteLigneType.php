<?php

namespace App\Form;

use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\VenteLigne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenteLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('medicament', EntityType::class, [
                'class' => Medicament::class,
                'choice_label' => 'nom',
                'choice_attr' => function (?Medicament $m) {
                    return $m ? ['data-price' => $m->getPrixUnitaire()] : [];
                },
                
                'attr' => [
                'class' => 'form-select medicament-select select2-enable',
                'placeholder' => '— Choisir un médicament —',
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
                'attr' => ['class' => 'form-select lot-select select2-enable'],
            ])
            ->add('quantite', IntegerType::class, [
                'attr' => ['min' => 1, 'class' => 'form-control'],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VenteLigne::class,
        ]);
    }
}
