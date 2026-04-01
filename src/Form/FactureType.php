<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Enum\StatutFacture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = (bool) ($options['is_edit'] ?? false);

        $builder
            ->add('montantTotal', IntegerType::class, [
                'label' => 'Montant',
                'required' => true,
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

            ->add('consultation', EntityType::class, [
                'label' => 'Consultation',
                'class' => Consultation::class,
                'placeholder' => '— Sélectionner une consultation —',
                'required' => true,
                'disabled' => $isEdit,
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

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $facture = $event->getData();

            if (!$facture instanceof Facture) {
                return;
            }

            $event->getForm()->get('montantTotal')->setData($facture->getBaseTotal());
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $facture = $event->getData();

            if (!$facture instanceof Facture) {
                return;
            }

            $montantBase = max(0, $facture->getMontantTotal());
            $montantTotal = $facture->calculerMontantAvecTimbre($montantBase);
            $montantPaye = max(0, $facture->getMontantPaye());

            $facture->setMontantTotal($montantTotal);

            if ($montantPaye > $montantTotal) {
                $montantPaye = $montantTotal;
                $facture->setMontantPaye($montantPaye);
            }

            $reste = max(0, $montantTotal - $montantPaye);
            $facture->setResteAPayer($reste);

            if ($montantTotal === 0) {
                $facture->setStatut(StatutFacture::BROUILLON);
                $facture->setDatePaiement(null);
                return;
            }

            if ($montantPaye <= 0) {
                $facture->setStatut(StatutFacture::NON_PAYE);
                $facture->setDatePaiement(null);
                return;
            }

            if ($reste > 0) {
                $facture->setStatut(StatutFacture::PARTIELLEMENT_PAYE);
                $facture->setDatePaiement(null);
                return;
            }

            $facture->setStatut(StatutFacture::PAYE);

            if ($facture->getDatePaiement() === null) {
                $facture->setDatePaiement(new \DateTimeImmutable());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
            'is_edit' => false,
        ]);
    }
}