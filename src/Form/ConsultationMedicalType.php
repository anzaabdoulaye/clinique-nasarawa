<?php
// src/Form/ConsultationMedicalType.php
namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Cim10Code;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationMedicalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Constantes (Option A)
            ->add('poids', NumberType::class, [
                'required' => false,
                'label' => 'Poids (kg)',
                'scale' => 2,
            ])
            ->add('taille', NumberType::class, [
                'required' => false,
                'label' => 'Taille (cm)',
                'scale' => 0,
            ])
            ->add('temperature', NumberType::class, [
                'required' => false,
                'label' => 'Température (°C)',
                'scale' => 1,
            ])
            ->add('tensionArterielle', null, [
                'required' => false,
                'label' => 'Tension artérielle (ex: 120/80)',
            ])
            ->add('frequenceCardiaque', NumberType::class, [
                'required' => false,
                'label' => 'Fréquence cardiaque (bpm)',
                'scale' => 0,
            ])

            // Phase C : clinique
            ->add('motifs', TextareaType::class, [
                'required' => false,
                'label' => 'Motif',
                'attr' => ['rows' => 3],
            ])
            ->add('histoire', TextareaType::class, [
                'required' => false,
                'label' => 'Histoire / Anamnèse',
                'attr' => ['rows' => 4],
            ])
            ->add('examenClinique', TextareaType::class, [
                'required' => false,
                'label' => 'Examen clinique',
                'attr' => ['rows' => 4],
            ])
            ->add('diagnostic', TextareaType::class, [
                'required' => false,
                'label' => 'Diagnostic (texte)',
                'attr' => ['rows' => 3],
            ])
            // CIM10 optionnel (si entité créée)
            ->add('cim10', EntityType::class, [
                'class' => Cim10Code::class,
                'required' => false,
                'placeholder' => '— Aucun code CIM10 —',
            'label' => 'Diagnostic CIM10 (optionnel)',
                'choice_label' => fn(Cim10Code $c) => $c->getCode().' - '.$c->getLibelle(),
                'attr' => [
                    'class' => 'form-control select2-enable' 
                ],
            ])
            ->add('conduiteATenir', TextareaType::class, [
                'required' => false,
                'label' => 'Conduite à tenir',
                'attr' => ['rows' => 4],
            ])

             ->add('poids', NumberType::class, ['required' => false, 'label' => 'Poids (kg)'])
            ->add('taille', NumberType::class, ['required' => false, 'label' => 'Taille (cm)'])
            ->add('temperature', NumberType::class, ['required' => false, 'label' => 'Température (°C)'])
            ->add('tensionArterielle', null, ['required' => false, 'label' => 'Tension artérielle'])
            ->add('frequenceCardiaque', NumberType::class, ['required' => false, 'label' => 'Fréquence cardiaque (bpm)'])


              ->add('motifs', TextareaType::class, ['required' => false, 'label' => 'Motif', 'attr' => ['rows' => 3]])
            ->add('histoire', TextareaType::class, ['required' => false, 'label' => 'Histoire / Anamnèse', 'attr' => ['rows' => 4]])
            ->add('examenClinique', TextareaType::class, ['required' => false, 'label' => 'Examen clinique', 'attr' => ['rows' => 4]])
            ->add('diagnostic', TextareaType::class, ['required' => false, 'label' => 'Diagnostic (texte)', 'attr' => ['rows' => 3]])
            ->add('conduiteATenir', TextareaType::class, ['required' => false, 'label' => 'Conduite à tenir', 'attr' => ['rows' => 4]])



        ;
         // CIM10 optionnel (ajoute seulement si tu as l'entité + relation)
        $builder->add('cim10', EntityType::class, [
            'class' => Cim10Code::class,
            'required' => false,
            'placeholder' => '— Aucun code CIM10 —',
            'label' => 'Diagnostic CIM10 (optionnel)',
            'choice_label' => fn(Cim10Code $c) => $c->getCode().' - '.$c->getLibelle(),
        ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}