<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // On utilise 'nom' ici car c'est le champ dans votre Entité User
            ->add('nom', TextType::class, [
                'label' => 'Username',
                'attr' => ['placeholder' => 'Username'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a username']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Your username should be at least {{ limit }} characters',
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'Email'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email']),
                    new Email(['message' => 'The email "{{ value }}" is not a valid email.']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // mapped => false signifie que ce champ n'est pas directement lié à l'entité User 
                // (on le hashera manuellement dans le contrôleur)
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password', 'placeholder' => 'Password'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length([
                        'min' => 6, // Votre règle était 8 dans le contrôleur manuel, ajustez si besoin
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            // Si vous avez besoin des conditions d'utilisation, décommentez ceci :
            /*
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'You should agree to our terms.']),
                ],
            ])
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class, // Le formulaire map vers l'entité User par défaut
        ]);
    }
}