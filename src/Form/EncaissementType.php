<?php

namespace App\Form;

use App\Enum\ModePaiement;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EncaissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxAmount = $options['max_amount'];

        $builder
            ->add('montant', IntegerType::class, [
                'label' => 'Montant encaissé',
                'constraints' => [
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Le montant du paiement doit être supérieur à zéro.',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $maxAmount,
                        'message' => 'Le montant saisi ne doit pas dépasser le reste à payer.',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => $maxAmount,
                    'placeholder' => 'Montant en FCFA',
                    'data-max-amount' => $maxAmount,
                ],
            ])
            ->add('mode', ChoiceType::class, [
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
                    'class' => 'form-control' 
                ],
            ])
            ->add('maxAmount', HiddenType::class, [
                'mapped' => false,
                'data' => (string) $maxAmount,
                'attr' => [
                    'class' => 'js-max-amount',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'max_amount' => 0,
        ]);

        $resolver->setAllowedTypes('max_amount', 'int');
    }
}