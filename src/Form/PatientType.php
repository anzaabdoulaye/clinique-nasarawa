<?php

namespace App\Form;

use App\Entity\Patient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateNaissance = $builder->getData()?->getDateNaissance();

        $builder
            // ===== Identité =====
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: ABDOULAYE',
                    'autocomplete' => 'family-name',
                ],
                'help' => 'Obligatoire.',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom(s)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Anza',
                    'autocomplete' => 'given-name',
                ],
                'help' => 'Obligatoire.',
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTimeImmutable())->format('Y-m-d'),
                    'data-date-naissance' => 'true',
                ],
                'help' => 'Vous pouvez saisir l’âge ou la date de naissance. Les deux champs se synchronisent automatiquement.',
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge (ans)',
                'required' => false,
                'mapped' => false,
                'data' => $this->calculateAge($dateNaissance),
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 130,
                    'placeholder' => 'Ex: 35',
                    'inputmode' => 'numeric',
                    'data-age-input' => 'true',
                ],
                'help' => 'Vous pouvez saisir l’âge ou la date de naissance. Les deux champs se synchronisent automatiquement.',
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin'  => 'F',
                ],
                'placeholder' => '— Choisir —',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ]) 
          

            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: +227 90 00 00 00',
                    'inputmode' => 'tel',
                    'autocomplete' => 'tel',
                ],
                'help' => 'Obligatoire. Format conseillé : +227 XX XX XX XX',
                
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Quartier, rue, ville…',
                ],
            ])

            // ===== Contact d'urgence =====
            ->add('emergencyContactName', TextType::class, [
                'label' => 'Contact d’urgence - Nom',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Issa Mahamadou',
                ],
            ])
            ->add('emergencyContactRelation', TextType::class, [
                'label' => 'Relation',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Père / Mère / Frère / Conjoint…',
                ],
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => 'Téléphone du proche',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: +227 90 00 00 00',
                    'inputmode' => 'tel',
                ],
            ])
            ->add('emergencyContactAddress', TextType::class, [
                'label' => 'Adresse du proche',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Adresse du contact d’urgence',
                ],
            ])

            // ===== Infos médicales rapides =====
            ->add('groupeSanguin', ChoiceType::class, [
                'label' => 'Groupe sanguin',
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
                'placeholder' => '— Choisir —',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('taille', TextType::class, [
                'label' => 'Taille',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 1.72 (m)',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('poids', TextType::class, [
                'label' => 'Poids',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 70 (kg)',
                    'inputmode' => 'decimal',
                ],
            ])

            // ===== Antécédents (Textarea: mieux que TextType) =====
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex: Pénicilline, arachide…',
                ],
            ])
            ->add('antecedentsMedicaux', TextareaType::class, [
                'label' => 'Antécédents médicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('antecedentsChirurgicaux', TextareaType::class, [
                'label' => 'Antécédents chirurgicaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('maladiesChroniques', TextareaType::class, [
                'label' => 'Maladies chroniques',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('traitementEnCours', TextareaType::class, [
                'label' => 'Traitement en cours',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('handicap', TextareaType::class, [
                'label' => 'Handicap',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])

            // ===== Grossesse =====
            ->add('grossesse', ChoiceType::class, [
                'label' => 'Grossesse',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'placeholder' => '— Indifférent —',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            // ===== Code (affiché, non éditable) =====
            ->add('code', TextType::class, [
                'label' => 'Code patient',
                'required' => false,
                'disabled' => true,
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Auto-généré',
                ],
                'help' => 'Généré automatiquement à la création.',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            $age = isset($data['age']) ? trim((string) $data['age']) : '';
            $dateNaissance = isset($data['dateNaissance']) ? trim((string) $data['dateNaissance']) : '';

            if ($age === '' || $dateNaissance !== '') {
                return;
            }

            if (!ctype_digit($age)) {
                return;
            }

            $ageInt = (int) $age;
            if ($ageInt < 0 || $ageInt > 130) {
                return;
            }

            $year = (int) (new \DateTimeImmutable())->format('Y') - $ageInt;
            $data['dateNaissance'] = sprintf('%04d-01-01', $year);

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }

    private function calculateAge(?\DateTimeInterface $dateNaissance): ?int
    {
        if (!$dateNaissance instanceof \DateTimeInterface) {
            return null;
        }

        return $dateNaissance->diff(new \DateTimeImmutable())->y;
    }
}