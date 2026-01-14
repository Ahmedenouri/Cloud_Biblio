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
    // ======================================================
    // PARTIE 1 : GESTION ADMIN / BIBLIOTHECAIRE
    // ======================================================

    #[Route('/gestion', name: 'app_emprunt_index')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function index(EmpruntRepository $empruntRepository): Response
    {
        // On trie : d'abord par statut (En attente en premier), puis par date
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findBy([], ['statut' => 'ASC', 'dateEmprunt' => 'DESC']),
        ]);
    }

    #[Route('/valider/{id}', name: 'app_emprunt_valider')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function valider(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        // DÉMARRAGE DU CHRONO : C'est ici que les 5 jours commencent
        $now = new \DateTime();
        $retourPrevu = (clone $now)->modify('+5 days');

        $emprunt->setStatut('EN_COURS');
        $emprunt->setDateEmprunt($now); // Date réelle de début
        $emprunt->setDateRetourPrevue($retourPrevu);
        $emprunt->setBibliothecaire($this->getUser());

        $em->flush();

        $this->addFlash('success', 'Emprunt validé. Le compteur de 5 jours est lancé.');
        return $this->redirectToRoute('app_emprunt_index');
    }

    #[Route('/refuser/{id}', name: 'app_emprunt_refuser')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function refuser(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        // On remet le livre en stock car la demande est annulée
        $livre = $emprunt->getLivre();
        $livre->setStock($livre->getStock() + 1);
        
        $emprunt->setStatut('REFUSE');
        $emprunt->setBibliothecaire($this->getUser());
        $em->flush();

        $this->addFlash('warning', 'Demande refusée. Stock rétabli.');
        return $this->redirectToRoute('app_emprunt_index');
    }

    #[Route('/confirmer-retour-admin/{id}', name: 'app_emprunt_retour_admin')]
    #[IsGranted('ROLE_BIBLIOTHECAIRE')]
    public function confirmerRetourAdmin(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        // C'est l'action faite par l'admin quand le client rapporte le livre physiquement
        $now = new \DateTime();
        
        // Calcul du retard et amende
        if ($emprunt->getDateRetourPrevue() && $now > $emprunt->getDateRetourPrevue()) {
            $interval = $now->diff($emprunt->getDateRetourPrevue());
            $joursRetard = $interval->days;
            $amende = $joursRetard * 20; // 20 MAD par jour
            
            $emprunt->setAmende($amende);
            $this->addFlash('error', "Retour tardif ! Amende générée : $amende MAD.");
        } else {
            $this->addFlash('success', 'Retour effectué dans les temps.');
        }

        $emprunt->setStatut('RENDU');
        $emprunt->setDateRetourReelle($now);

        // Remise en stock
        $livre = $emprunt->getLivre();
        $livre->setStock($livre->getStock() + 1);

        $em->flush();

        return $this->redirectToRoute('app_emprunt_index');
    }

    // ======================================================
    // PARTIE 2 : ESPACE CLIENT (USER)
    // ======================================================

    // C'EST CETTE ROUTE QUI MANQUAIT ET QUI CAUSAIT L'ERREUR
    #[Route('/mes-demandes', name: 'app_mes_demandes')]
    #[IsGranted('ROLE_USER')]
    public function mesDemandes(EmpruntRepository $empruntRepo, CommandeRepository $commandeRepo): Response
    {
        return $this->render('emprunt/mes_demandes.html.twig', [
            'emprunts' => $empruntRepo->findBy(['user' => $this->getUser()], ['dateEmprunt' => 'DESC']),
            'commandes' => $commandeRepo->findBy(['user' => $this->getUser()], ['dateCommande' => 'DESC']),
        ]);
    }

    #[Route('/rendre-client/{id}', name: 'app_emprunt_return')]
    #[IsGranted('ROLE_USER')]
    public function rendreClient(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        // Sécurité : Vérifier que c'est bien l'emprunt du user connecté
        if ($emprunt->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Le client déclare qu'il rend le livre, mais c'est l'admin qui valide le stock final normalement.
        // Dans votre scénario, on peut dire que cette action arrête le compteur pour le client
        // ou on le redirige simplement vers l'accueil en disant "Veuillez rapporter le livre".
        
        // Pour simplifier selon votre demande précédente (bouton user) :
        // On considère que le clic ici vaut retour (ex: borne automatique ou confiance)
        
        // (Copie de la logique retour simple si vous voulez que le client puisse clore lui-même)
        // Mais logiquement, c'est l'admin qui devrait cliquer sur "Confirmer Retour" ci-dessus.
        
        // Si vous voulez juste afficher un message :
        $this->addFlash('info', 'Veuillez rapporter ce livre à la bibliothèque pour clôturer l\'emprunt.');
        return $this->redirectToRoute('app_mes_demandes');
    }
}