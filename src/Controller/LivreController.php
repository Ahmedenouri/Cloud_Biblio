<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\LivrePdf;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    #[Route(name: 'app_livre_index', methods: ['GET'])]
    public function index(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager): Response {
    $livre = new Livre();
    $form = $this->createForm(LivreType::class, $livre);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔹 Gestion PDF
        $pdfFile = $form->get('pdfFile')->getData();

        if ($livre->getType() === 'pdf' && $pdfFile) {

            $pdfFilename = uniqid().'.'.$pdfFile->guessExtension();
            $pdfFile->move(
                $this->getParameter('pdf_directory'),
                $pdfFilename
            );

            $livrePdf = new LivrePdf();
            $livrePdf->setFichier($pdfFilename);
            $livrePdf->setLivre($livre);

            $livre->setPdf($livrePdf);
        }

        // 🔹 CAS LIVRE PHYSIQUE (ICI ⬇️)
        if ($livre->getType() === 'physique') {
            $livre->setPdf(null);
        }

        $entityManager->persist($livre);
        $entityManager->flush();

        return $this->redirectToRoute('app_livre_index');
    }

    return $this->render('livre/new.html.twig', [
        'livre' => $livre,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_livre_show', methods: ['GET'])]
    public function show(Livre $livre): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }
        $pdfFile = $form->get('pdfFile')->getData();

        if ($livre->getType() === 'pdf' && $pdfFile instanceof UploadedFile) {

            $pdfFilename = uniqid().'.'.$pdfFile->guessExtension();

            $pdfFile->move(
                $this->getParameter('pdf_directory'),
                $pdfFilename
            );

            $livrePdf = new LivrePdf();
            $livrePdf->setFichier($pdfFilename);
            $livrePdf->setLivre($livre);

            $livre->setPdf($livrePdf);
        }
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();

            // Déplace le fichier dans public/uploads/images
            $imageFile->move(
                $this->getParameter('images_directory'),
                $newFilename
            );

            // Sauvegarde le nom du fichier dans l'entity
            $livre->setImage($newFilename);
        }
        $entityManager->persist($livre);
        $entityManager->flush();



        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
        
    }

    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}
