<?php

namespace App\Form;

use App\Enum\ModePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncaissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'label' => 'Montant encaissé',
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Montant en FCFA',
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}