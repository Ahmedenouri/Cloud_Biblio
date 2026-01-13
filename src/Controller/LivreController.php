<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\LivrePdf;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    // ACCESSIBLE À TOUT LE MONDE (Public)
    #[Route('/', name: 'app_livre_index', methods: ['GET'])]
    public function index(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }

    // RÉSERVÉ AUX ADMINS ET BIBLIOTHECAIRES
    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. Gestion PDF (Indépendant du type de livre)
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile instanceof UploadedFile) {
                $pdfFilename = uniqid().'.'.$pdfFile->guessExtension();
                $pdfFile->move($this->getParameter('pdf_directory'), $pdfFilename);

                $livrePdf = new LivrePdf();
                $livrePdf->setFichier($pdfFilename);
                $livrePdf->setLivre($livre);
                $livre->setPdf($livrePdf);
            }

            // 2. Gestion Image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile instanceof UploadedFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $livre->setImage($newFilename);
            }

            $entityManager->persist($livre);
            $entityManager->flush();

            $this->addFlash('success', 'Livre créé avec succès !');
            return $this->redirectToRoute('app_livre_index');
        }

        return $this->render('livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    // ACCESSIBLE À TOUT LE MONDE (Public)
    #[Route('/{id}', name: 'app_livre_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Livre $livre): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }

    // RÉSERVÉ AUX ADMINS ET BIBLIOTHECAIRES
    #[Route('/{id}/edit', name: 'app_livre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. Mise à jour PDF
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile instanceof UploadedFile) {
                $pdfFilename = uniqid().'.'.$pdfFile->guessExtension();
                $pdfFile->move($this->getParameter('pdf_directory'), $pdfFilename);

                // On récupère le PDF existant ou on en crée un nouveau
                $livrePdf = $livre->getPdf() ?? new LivrePdf();
                $livrePdf->setFichier($pdfFilename);
                $livrePdf->setLivre($livre);
                $livre->setPdf($livrePdf);
            }

            // 2. Mise à jour Image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile instanceof UploadedFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $livre->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Livre modifié avec succès !');
            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    // RÉSERVÉ AUX ADMINS ET BIBLIOTHECAIRES
    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Livre supprimé.');
        }

        return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
    }

    // --- ACTIONS UTILISATEUR ---

    #[Route('/{id}/emprunter', name: 'app_livre_emprunt')]
    #[IsGranted('ROLE_USER')] // Oblige d'être connecté
    public function emprunter(Livre $livre, EntityManagerInterface $em): Response
    {
        // Vérification de stock (Sécurité côté serveur)
        if ($livre->getStock() <= 0) {
            $this->addFlash('error', 'Désolé, ce livre n\'est plus en stock.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        // TODO: Créer l'entité Emprunt ici et décrémenter le stock
        // $livre->setStock($livre->getStock() - 1);
        // $em->flush();

        $this->addFlash('success', 'Demande d\'emprunt envoyée !');
        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    #[Route('/{id}/telecharger', name: 'app_livre_download')]
    #[IsGranted('ROLE_USER')] // Oblige d'être connecté
    public function telecharger(Livre $livre): Response
    {
        // On vérifie s'il y a bien un PDF lié, peu importe le "type"
        if (!$livre->getPdf()) {
             throw $this->createNotFoundException('Aucun fichier PDF n\'est associé à ce livre.');
        }

        return $this->file($this->getParameter('pdf_directory').'/'.$livre->getPdf()->getFichier());
    }
}