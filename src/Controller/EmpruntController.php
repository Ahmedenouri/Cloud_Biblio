<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Repository\CommandeRepository;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/emprunt')]
class EmpruntController extends AbstractController
{
    // --- PARTIE ADMIN / BIBLIOTHECAIRE ---

    #[Route('/gestion', name: 'app_emprunt_index')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function index(EmpruntRepository $empruntRepository): Response
    {
        // Le bibliothécaire voit TOUS les emprunts, triés par date
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findBy([], ['dateEmprunt' => 'DESC']),
        ]);
    }

    #[Route('/valider/{id}', name: 'app_emprunt_valider')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function valider(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        $emprunt->setStatut('VALIDE');
        $emprunt->setBibliothecaire($this->getUser()); // On enregistre qui a validé
        $em->flush();

        $this->addFlash('success', 'Emprunt validé avec succès.');
        return $this->redirectToRoute('app_emprunt_index');
    }

    #[Route('/refuser/{id}', name: 'app_emprunt_refuser')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function refuser(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        // On remet le stock si on refuse ? (Optionnel)
        $livre = $emprunt->getLivre();
        $livre->setStock($livre->getStock() + 1);
        
        $emprunt->setStatut('REFUSE');
        $emprunt->setBibliothecaire($this->getUser());
        $em->flush();

        $this->addFlash('warning', 'Emprunt refusé.');
        return $this->redirectToRoute('app_emprunt_index');
    }

    // --- PARTIE CLIENT / USER ---

    #[Route('/mes-demandes', name: 'app_mes_demandes')]
    #[IsGranted('ROLE_USER')]
    public function mesDemandes(EmpruntRepository $empruntRepo, CommandeRepository $commandeRepo): Response
    {
        return $this->render('emprunt/mes_demandes.html.twig', [
            'emprunts' => $empruntRepo->findBy(['user' => $this->getUser()], ['dateEmprunt' => 'DESC']),
            'commandes' => $commandeRepo->findBy(['user' => $this->getUser()], ['dateCommande' => 'DESC']),
        ]);
    }
}