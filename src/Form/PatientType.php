<?php

namespace App\Form;

use App\Entity\Patient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('telephone', IntegerType::class, [
                'required' => false,
                // 'html5' => true, // OK
            ])
            ->add('sexe', ChoiceType::class, [
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin'  => 'F',
                ],
                'placeholder' => 'Choisir...',
            ])
            ->add('adresse', TextType::class, [
                'required' => false,
            ])
            ->add('emergencyContactName', TextType::class, [
                'required' => false,
                'label' => 'Contact d\'urgence - Nom'
            ])
            ->add('emergencyContactRelation', TextType::class, [
                'required' => false,
                'label' => 'Relation'
            ])
            ->add('emergencyContactPhone', TextType::class, [
                'required' => false,
                'label' => 'Téléphone du proche'
            ])
            ->add('emergencyContactAddress', TextType::class, [
                'required' => false,
                'label' => 'Adresse du proche'
            ])
            ->add('groupeSanguin', ChoiceType::class, [
                'choices' => [
                    'A+' => 'A+',
                    'A-' => 'A-',
                    'B+' => 'B+',
                    'B-' => 'B-',
                    'AB+' => 'AB+',
                    'AB-' => 'AB-',
                    'O+' => 'O+',
                    'O-' => 'O-',
                ],
                'placeholder' => 'Choisir...',
                'required' => false,
            ])
            ->add('taille', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'en m'],
            ])
            ->add('poids', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'en kg'],
            ])
            ->add('allergies', TextType::class, [
                'required' => false,
            ])
            ->add('antecedentsMedicaux', TextType::class, [
                'required' => false,
            ])
            ->add('antecedentsChirurgicaux', TextType::class, [
                'required' => false,
            ])
            ->add('maladiesChroniques', TextType::class, [
                'required' => false,
            ])
            ->add('traitementEnCours', TextType::class, [
                'required' => false,
            ])
            ->add('handicap', TextType::class, [
                'required' => false,
            ])
            ->add('grossesse', ChoiceType::class, [
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'placeholder' => 'Indifférent',
                'required' => false,
            ])
            // Code affiché mais non éditable (généré côté serveur)
            ->add('code', TextType::class, [
                'required' => false,
                'disabled' => true,
                'mapped' => true, // on affiche la valeur de l'entité
                'attr' => [
                    'placeholder' => 'Auto-généré',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}
