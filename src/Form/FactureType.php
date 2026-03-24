<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Enum\StatutFacture;
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
        $builder
             ->add('montantTotal', NumberType::class, [ 
                'label' => 'Montant Total de la Facture',
                'required' => true,
                'scale' => 0, 
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => 'Ex: 15000',
                ],
            ])

            

            ->add('montantPaye', NumberType::class, [
                'label' => 'Montant payé',
                'required' => false,
                'scale' => 0,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 1,
                ],
            ])

            ->add('resteAPayer', NumberType::class, [
                'label' => 'Reste à payer',
                'required' => false,
                'scale' => 0,
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true,
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

            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutFacture::class,
                'placeholder' => '— Choisir —',
                'required' => true,
                'choice_label' => fn (StatutFacture $s) => $s->value,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('consultation', EntityType::class, [
                'label' => 'Consultation',
                'class' => Consultation::class,
                'required' => true,
                'choice_label' => function (Consultation $c) {
                    $id = $c->getId();

                    $date = method_exists($c, 'getDateHeure') && $c->getDateHeure()
                        ? $c->getDateHeure()->format('d/m/Y H:i')
                        : null;

                    $patient = null;
                    if (
                        method_exists($c, 'getRendezVous')
                        && $c->getRendezVous()
                        && method_exists($c->getRendezVous(), 'getPatient')
                        && $c->getRendezVous()->getPatient()
                    ) {
                        $p = $c->getRendezVous()->getPatient();
                        $patient = trim(($p->getNom() ?? '') . ' ' . ($p->getPrenom() ?? ''));
                    }

                    $parts = array_filter([
                        'CONS-' . $id,
                        $patient ?: null,
                        $date ? '(' . $date . ')' : null,
                    ]);

                    return implode(' | ', $parts);
                },
                'attr' => [
                    'class' => 'form-control select2-enable',
                    'placeholder' => '— Sélectionner une consultation —',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
            'is_edit' => false,
        ]);
    }
}