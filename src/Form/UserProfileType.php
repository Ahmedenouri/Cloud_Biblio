<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Style commun pour les inputs
        $style = 'w-full bg-[#F5F1E6]/40 border-none rounded-xl p-3 focus:ring-2 focus:ring-[#8B5E3C] transition-all';

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['class' => $style]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => $style]
            ])
            // Champ Photo (Non mappé à la BDD directement)
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
            ])
            // Réseaux sociaux
            ->add('facebook', TextType::class, ['required' => false, 'attr' => ['class' => $style, 'placeholder' => 'Lien Facebook']])
            ->add('instagram', TextType::class, ['required' => false, 'attr' => ['class' => $style, 'placeholder' => 'Pseudo ou lien Instagram']])
            ->add('linkedin', TextType::class, ['required' => false, 'attr' => ['class' => $style, 'placeholder' => 'Lien LinkedIn']])
            ->add('x', TextType::class, ['required' => false, 'attr' => ['class' => $style, 'placeholder' => 'Lien X (Twitter)']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}