<?php

namespace App\Form;

use App\Entity\Lot;
use App\Enum\TypeMouvementStock;
use App\Repository\LotRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lot', EntityType::class, [
                'class' => Lot::class,
                'query_builder' => function (LotRepository $repo) {
                    // On n'affiche que les lots qui ont un stock positif
                    return $repo->createQueryBuilder('l')
                        ->where('l.quantite > 0')
                        ->leftJoin('l.medicament', 'm')->addSelect('m')
                        ->orderBy('m.nom', 'ASC')
                        ->addOrderBy('l.datePeremption', 'ASC');
                },
                'choice_label' => function (Lot $lot) {
                    $datePeremption = $lot->getDatePeremption() ? $lot->getDatePeremption()->format('m/Y') : 'N/A';
                    return sprintf('%s — Lot: %s (Reste: %d) [Pér: %s]', 
                        $lot->getMedicament()->getNom(), 
                        $lot->getNumeroLot() ?: 'Sans lot', 
                        $lot->getQuantite(),
                        $datePeremption
                    );
                },
                'label' => 'Sélectionnez le lot à déduire',
                'attr' => ['class' => 'form-select shadow-sm']
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité à sortir',
                'attr' => ['min' => 1, 'class' => 'form-control shadow-sm fw-bold text-danger']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Motif de la sortie',
                'choices' => [
                    'Dotation à un service (ex: Urgences, Bloc)' => TypeMouvementStock::SORTIE_SERVICE,
                    'Perte, Casse ou Péremption' => TypeMouvementStock::SORTIE_PERTE,
                    'Ajustement d\'inventaire' => TypeMouvementStock::SORTIE_AJUSTEMENT,
                ],
                'attr' => ['class' => 'form-select shadow-sm']
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Commentaire / Justification',
                'required' => true,
                'attr' => ['rows' => 2, 'class' => 'form-control shadow-sm', 'placeholder' => 'Précisez la raison...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}