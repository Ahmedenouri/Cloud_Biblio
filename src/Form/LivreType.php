<?php

namespace App\Form;

use App\Entity\Livre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du livre',
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-[#8d6e63]']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Résumé',
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-[#8d6e63]', 'rows' => 5]
            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'MAD',
                'label' => 'Prix',
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded']
            ])
            
            // TYPE : Sert à définir l'étiquette principale (Badge jaune ou bleu sur le site)
            ->add('type', ChoiceType::class, [
                'label' => 'Format principal (Affichage)',
                'choices' => [
                    'Livre Physique (Papier)' => 'physique',
                    'Livre Numérique (PDF)' => 'pdf',
                ],
                'expanded' => true, // Boutons radio
                'multiple' => false,
                'attr' => ['class' => 'flex gap-6 mt-2 mb-4']
            ])

            ->add('stock', IntegerType::class, [
                'label' => 'Stock (Nombre d\'exemplaires physiques)',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded']
            ])

            ->add('categorie', ChoiceType::class, [
                'choices' => [
                    'Roman' => 'Roman',
                    'Science-Fiction' => 'Science-Fiction',
                    'Histoire' => 'Histoire',
                    'Informatique' => 'Informatique',
                    'Développement Personnel' => 'Développement Personnel',
                ],
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded']
            ])
            
            // IMAGE
            ->add('imageFile', FileType::class, [
                'label' => 'Couverture (Image)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded bg-white']
            ])

            // PDF : Toujours accessible pour upload
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF (Optionnel)',
                'mapped' => false,
                'required' => false,
                'help' => 'Vous pouvez ajouter un PDF même si le livre est vendu en physique.',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un PDF valide',
                    ])
                ],
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded bg-white']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livre::class,
        ]);
    }
}