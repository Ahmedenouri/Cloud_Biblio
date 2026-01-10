<?php

namespace App\Controller;

use App\Entity\LivrePdf;
use App\Form\LivrePdfType;
use App\Repository\LivrePdfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/livre/pdf')]
final class LivrePdfController extends AbstractController
{
    #[Route(name: 'app_livre_pdf_index', methods: ['GET'])]
    public function index(LivrePdfRepository $livrePdfRepository): Response
    {
        return $this->render('livre_pdf/index.html.twig', [
            'livre_pdfs' => $livrePdfRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livre_pdf_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livrePdf = new LivrePdf();
        $form = $this->createForm(LivrePdfType::class, $livrePdf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($livrePdf);
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_pdf_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre_pdf/new.html.twig', [
            'livre_pdf' => $livrePdf,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_pdf_show', methods: ['GET'])]
    public function show(LivrePdf $livrePdf): Response
    {
        return $this->render('livre_pdf/show.html.twig', [
            'livre_pdf' => $livrePdf,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livre_pdf_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LivrePdf $livrePdf, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivrePdfType::class, $livrePdf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_pdf_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre_pdf/edit.html.twig', [
            'livre_pdf' => $livrePdf,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_pdf_delete', methods: ['POST'])]
    public function delete(Request $request, LivrePdf $livrePdf, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livrePdf->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livrePdf);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_pdf_index', [], Response::HTTP_SEE_OTHER);
    }
}
