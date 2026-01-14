<?php

namespace App\Controller;

use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile; // Indispensable
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/mon-profil', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Création du formulaire
        $form = $this->createForm(UserProfileType::class, $user);
        
        // 2. Traitement de la requête (CRUCIAL pour que le bouton Enregistrer marche)
        $form->handleRequest($request);

        // 3. Vérification si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            // Gestion de l'upload photo
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                    $user->setProfile($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_profile');
                }
            }

            // Sauvegarde en BDD
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }
        
        // 4. Gestion des erreurs de validation (si le formulaire est invalide)
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Attention, le formulaire contient des erreurs.');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user, // On passe l'user explicitement même si app.user existe
        ]);
    }
}