<?php

namespace App\Form;

use App\Entity\Livre;
use App\Entity\LivrePdf;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('prix')
            ->add('type', ChoiceType::class, [
                    'choices' => [
                        'Physique' => 'physique',
                        'PDF' => 'pdf',
                    ],
                ])
            ->add('stock')
            ->add('categorie', ChoiceType::class, [
                    'choices' => [
                        'Roman' => 'roman',
                        'Science' => 'science',
                        'Histoire' => 'histoire',
                        'Informatique' => 'informatique',
                    ],
                    'placeholder' => 'Choisir une catégorie',
                ])
            ->add('imageFile', FileType::class, [
                    'label' => 'Image du livre',
                    'mapped' => false, // important : ce n’est pas directement dans l’entity
                    'required' => false,
                    'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp'
                            ],
                            'mimeTypesMessage' => 'Veuillez uploader une image valide (jpg, png, webp)',
                        ])
                    ]
                ])

            ->add('createdAt')
            ->add('updatedAt')
            
            ->add('pdfFile', FileType::class, [
                    'label' => 'Fichier PDF',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [
                        new File([
                            'mimeTypes' => ['application/pdf'],
                            'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                        ]),
                    ],
                ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livre::class,
        ]);
    }
}
