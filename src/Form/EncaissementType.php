<?php

namespace App\Form;

use App\Entity\Facture;
use App\Entity\OrganismePriseEnCharge;
use App\Enum\ModePaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class EncaissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxAmount = $options['max_amount'];

        $builder
            ->add('priseEnChargeActive', CheckboxType::class, [
                'label' => 'Prise en charge',
                'required' => false,
            ])
            ->add('organismePriseEnCharge', EntityType::class, [
                'class' => OrganismePriseEnCharge::class,
                'choice_label' => 'nom',
                'placeholder' => '— Sélectionner un organisme —',
                'required' => false,
                'label' => 'Organisme',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('tauxPriseEnChargeManuel', ChoiceType::class, [
                'label' => 'Taux PEC',
                'required' => false,
                'placeholder' => 'Choisir un taux',
                'choices' => [
                    '50 %' => 50,
                    '60 %' => 60,
                    '70 %' => 70,
                    '80 %' => 80,
                    '90 %' => 90,
                    '100 %' => 100,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('montant', IntegerType::class, [
                'mapped' => false,
                'label' => 'Montant encaissé',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un montant.',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le montant doit être supérieur ou égal à zéro.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    // 'max' => $maxAmount, // Plus besoin
                    'placeholder' => 'Montant en FCFA',
                    'class' => 'form-control',
                ],
            ])
            ->add('mode', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Mode de paiement',
                'choices' => [
                    'Espèce' => ModePaiement::ESPECES,
                    'Mobile Money' => ModePaiement::MOBILE_MONEY,
                    'Carte' => ModePaiement::CARTE,
                    'Virement' => ModePaiement::VIREMENT,
                ],
                'choice_value' => fn (?ModePaiement $choice) => $choice?->value,
                'choice_label' => fn (ModePaiement $choice) => match ($choice) {
                    ModePaiement::ESPECES => 'Espèce',
                    ModePaiement::MOBILE_MONEY => 'Mobile Money',
                    ModePaiement::CARTE => 'Carte',
                    ModePaiement::VIREMENT => 'Virement',
                },
                'placeholder' => 'Choisir un mode',
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
            'max_amount' => 0,
        ]);

        $resolver->setAllowedTypes('max_amount', 'int');
    }
}