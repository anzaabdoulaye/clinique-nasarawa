<?php

namespace App\Form;

use App\Entity\ServiceMedical;
use App\Entity\Utilisateur;
use App\Enum\StatutUtilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = (bool) $options['is_new'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Abdoulaye',
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Anza',
                ],
            ])
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: a.anza',
                    'autocomplete' => 'username',
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'data' => '1234', // ✅ valeur par défaut
                'label' => $isNew ? 'Mot de passe (optionnel)' : 'Nouveau mot de passe (optionnel)',
                'help' => $isNew
                    ? 'Laissez 1234 ou modifiez-le.'
                    : 'Laissez vide pour conserver le mot de passe actuel.',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
            ])

            
            // ->add('plainPassword', PasswordType::class, [
            //     'mapped' => false,
            //     'required' => false,
            //     'label' => $isNew ? 'Mot de passe (optionnel)' : 'Nouveau mot de passe (optionnel)',
            //     'help' => $isNew
            //         ? 'Laissez vide pour générer un mot de passe temporaire.'
            //         : 'Laissez vide pour conserver le mot de passe actuel.',
            //     'attr' => [
            //         'class' => 'form-control',
            //         'placeholder' => $isNew ? 'Laisser vide pour générer' : 'Laisser vide pour ne pas changer',
            //         'autocomplete' => $isNew ? 'new-password' : 'new-password',
            //     ],
            // ])

            ->add('statut', EnumType::class, [
                'class' => StatutUtilisateur::class,
                'label' => 'Statut',
                'choice_label' => fn (StatutUtilisateur $s) => $s->label(),
                'placeholder' => 'Choisir...',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Médecin' => 'ROLE_MEDECIN',
                    'Infirmier(ère)' => 'ROLE_INFIRMIER',
                    'Réception' => 'ROLE_RECEPTION',
                    'RH' => 'ROLE_RH',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'mt-1',
                ],
            ])

            ->add('serviceMedical', EntityType::class, [
                'class' => ServiceMedical::class,
                'choice_label' => 'libelle', 
                'label' => 'Service médical',
                'placeholder' => 'Choisir...',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_new' => true, 
        ]);

        $resolver->setAllowedTypes('is_new', 'bool');
    }
}