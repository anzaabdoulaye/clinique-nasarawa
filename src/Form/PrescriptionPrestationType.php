<?php

namespace App\Form;

use App\Entity\PrescriptionPrestation;
use App\Entity\TarifPrestation;
use App\Repository\TarifPrestationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrescriptionPrestationType extends AbstractType
{
    public function __construct(
        private TarifPrestationRepository $tarifPrestationRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tarifPrestation', EntityType::class, [
                'class' => TarifPrestation::class,
                'label' => 'Acte / examen / consommable',
                'placeholder' => 'Choisir une prestation',
                'choice_label' => function (TarifPrestation $tarif) {
                    return $tarif->getLibelle();
                },
                'query_builder' => function (TarifPrestationRepository $repo) {
                    return $repo->createQueryBuilder('t')
                        ->andWhere('t.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('t.libelle', 'ASC');
                },
                'attr' => [
                    'class' => 'select2-prestation',
                    'data-placeholder' => 'Rechercher une prestation...',
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'data' => 1,
                'attr' => ['min' => 1],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Précisions médicales, fréquence, durée, remarque...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionPrestation::class,
        ]);
    }
}