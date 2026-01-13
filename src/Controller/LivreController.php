<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\LivrePdf;
use App\Entity\LigneCommande; // Important pour la vérification
use App\Form\LivreType;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    // --- ACCESSIBLE À TOUT LE MONDE (Public) ---
    #[Route('/', name: 'app_livre_index', methods: ['GET'])]
    public function index(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }

    // --- CRÉATION (Admin/Bibliothécaire) ---
    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE', message: 'Accès réservé au personnel.')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $livre = new Livre();
        
        // CORRECTION IMPORTANTE :
        // Comme on a retiré le champ "Type" du formulaire, on le force ici
        // pour éviter l'erreur "Column 'type' cannot be null".
        $livre->setType('hybride'); 

        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. GESTION DE L'IMAGE
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur
                }
                $livre->setImage($newFilename);
            }

            // 2. GESTION DU PDF
            /** @var UploadedFile $pdfFile */
            $pdfFile = $form->get('pdfFile')->getData();

            if ($pdfFile) {
                $originalPdfName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safePdfName = $slugger->slug($originalPdfName);
                $pdfFilename = $safePdfName.'-'.uniqid().'.'.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('pdf_directory'),
                        $pdfFilename
                    );

                    $livrePdf = new LivrePdf();
                    $livrePdf->setFichier($pdfFilename);
                    $livre->setPdf($livrePdf); 

                } catch (FileException $e) {
                    // Gérer l'erreur
                }
            }

            $entityManager->persist($livre);
            $entityManager->flush();

            $this->addFlash('success', 'Livre ajouté avec succès !');
            return $this->redirectToRoute('app_livre_index');
        }

        return $this->render('livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    // --- DÉTAILS (Public) ---
    #[Route('/{id}', name: 'app_livre_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Livre $livre): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }

    // --- ÉDITION (Admin/Bibliothécaire) ---
    #[Route('/{id}/edit', name: 'app_livre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. UPDATE IMAGE
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $livre->setImage($newFilename);
            }

            // 2. UPDATE PDF
            /** @var UploadedFile $pdfFile */
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $pdfFilename = uniqid().'.'.$pdfFile->guessExtension();
                $pdfFile->move($this->getParameter('pdf_directory'), $pdfFilename);

                $livrePdf = $livre->getPdf();
                if (!$livrePdf) {
                    $livrePdf = new LivrePdf();
                    $livre->setPdf($livrePdf); 
                }
                $livrePdf->setFichier($pdfFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Livre modifié avec succès !');
            return $this->redirectToRoute('app_livre_index');
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    // --- SUPPRESSION (Admin/Bibliothécaire) ---
    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Livre supprimé.');
        }

        return $this->redirectToRoute('app_livre_index');
    }

    // --- TÉLÉCHARGEMENT PDF (Utilisateur connecté & Achat vérifié) ---
    #[Route('/{id}/telecharger', name: 'app_livre_download')]
    #[IsGranted('ROLE_USER')] 
    public function telecharger(Livre $livre, EntityManagerInterface $em): Response
    {
        // 1. Vérification basique
        if (!$livre->getPdf()) {
             throw $this->createNotFoundException('Aucun fichier PDF n\'est associé à ce livre.');
        }

        // 2. SÉCURITÉ : L'utilisateur a-t-il acheté ce PDF ?
        $user = $this->getUser();

        // On cherche une ligne de commande qui correspond à :
        // - Ce livre
        // - Cet utilisateur
        // - Type 'pdf'
        // - Statut de la commande 'VALIDE'
        $achat = $em->getRepository(LigneCommande::class)->createQueryBuilder('lc')
            ->join('lc.commande', 'c')
            ->where('c.user = :user')
            ->andWhere('lc.livre = :livre')
            ->andWhere('lc.type = :type') // On vérifie bien le type PDF
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('livre', $livre)
            ->setParameter('type', 'pdf')
            ->setParameter('statut', 'VALIDE')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Si aucun achat trouvé, on bloque
        if (!$achat) {
            throw $this->createAccessDeniedException('Vous devez acheter la version numérique pour télécharger ce fichier.');
        }

        // 3. Téléchargement autorisé
        $pdfPath = $this->getParameter('pdf_directory').'/'.$livre->getPdf()->getFichier();

        return $this->file($pdfPath, $livre->getTitre() . '.pdf');
    }
}