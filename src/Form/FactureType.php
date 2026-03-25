<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = (bool) ($options['is_edit'] ?? false);

        $builder
            ->add('montant', NumberType::class, [
                'label' => 'Montant',
                'required' => true,
                'scale' => 0, // mets 2 si tu gères les décimales
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => 'Ex: 15000',
                ],
            ])

            ->add('dateEmission', DateTimeType::class, [
                'label' => 'Date d’émission',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('datePaiement', DateTimeType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Laisser vide si la facture n’est pas encore payée.',
            ])

            // ✅ Si ce sont des PHP enum (recommandé)
            ->add('statutPaiement', EnumType::class, [
                'label' => 'Statut de paiement',
                'class' => StatutPaiement::class,
                'placeholder' => '— Choisir —',
                'required' => true,
                'choice_label' => fn (StatutPaiement $s) => $s->value, // ou ->name
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('modePaiement', EnumType::class, [
                'label' => 'Mode de paiement',
                'class' => ModePaiement::class,
                'placeholder' => '— Choisir —',
                'required' => false, // souvent on choisit le mode seulement si payé
                'choice_label' => fn (ModePaiement $m) => $m->value,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('consultation', EntityType::class, [
                'label' => 'Consultation',
                'class' => Consultation::class,
                'placeholder' => '— Sélectionner une consultation —',
                'required' => true,
                'choice_label' => function (Consultation $c) {
                    // Adaptation: à ajuster selon tes champs
                    // Exemple lisible: "CONS-12 | Patient X | 05/03/2026"
                    $id = $c->getId();
                    $date = method_exists($c, 'getDateHeure') && $c->getDateHeure()
                        ? $c->getDateHeure()->format('d/m/Y H:i')
                        : null;

                    $patient = null;
                    if (method_exists($c, 'getRendezVous') && $c->getRendezVous() && method_exists($c->getRendezVous(), 'getPatient') && $c->getRendezVous()->getPatient()) {
                        $p = $c->getRendezVous()->getPatient();
                        $patient = trim(($p->getNom() ?? '') . ' ' . ($p->getPrenom() ?? ''));
                    }

                    $parts = array_filter([
                        'CONS-' . $id,
                        $patient ?: null,
                        $date ? ('(' . $date . ')') : null,
                    ]);

                    return implode(' | ', $parts);
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
            'is_edit' => false,
        ]);
    }
}