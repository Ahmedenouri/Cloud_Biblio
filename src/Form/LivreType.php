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
use Symfony\Component\Validator\Constraints\NotBlank; // N'oublie pas cet import !

class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On récupère le livre pour voir s'il a déjà un PDF (mode modification)
        $livre = $builder->getData();
        $hasPdf = $livre && $livre->getPdf() !== null;

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
                // On précise que c'est le prix papier
                'label' => 'Prix Version Papier (MAD)', 
                'attr' => ['class' => 'w-full border border-gray-300 p-2 rounded']
            ])
            
            // SUPPRESSION DU CHAMP 'TYPE' (Car tous les livres sont maintenant hybrides)

            ->add('stock', IntegerType::class, [
                'label' => 'Stock Physique',
                'required' => true, // Le stock est requis pour la version papier
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

            // PDF : OBLIGATOIRE (Si nouveau livre)
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF (Obligatoire)',
                'mapped' => false,
                // Requis SEULEMENT s'il n'y a pas encore de PDF en base
                'required' => !$hasPdf, 
                'help' => 'La version PDF est vendue 30% moins chère automatiquement.',
                'constraints' => $hasPdf ? [] : [
                    new NotBlank(['message' => 'Veuillez uploader le PDF du livre (Obligatoire)']),
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