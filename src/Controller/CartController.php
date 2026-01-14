<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index')]
    public function index(RequestStack $requestStack, LivreRepository $livreRepository): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        $dataPanier = [];
        $totalAchat = 0;

        foreach ($panier as $id => $details) {
            $livre = $livreRepository->find($id);
            if ($livre) {
                $prixUnitaire = 0;

                if ($details['type'] === 'achat') {
                    $prixUnitaire = $livre->getPrix();
                } elseif ($details['type'] === 'pdf') {
                    $prixUnitaire = $livre->getPrixPdf();
                } elseif ($details['type'] === 'emprunt') {
                    // Prix location = 10%
                    $prixUnitaire = $livre->getPrix() * 0.10; 
                }

                $dataPanier[] = [
                    'livre' => $livre,
                    'quantity' => $details['quantity'],
                    'type' => $details['type'],
                    'prix' => $prixUnitaire
                ];
                
                $totalAchat += $prixUnitaire * $details['quantity'];
            }
        }

        return $this->render('cart/index.html.twig', [
            'dataPanier' => $dataPanier,
            'totalAchat' => $totalAchat
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(int $id, Request $request, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);
        
        $type = $request->query->get('type', 'emprunt'); 

        if (empty($panier[$id])) {
            $panier[$id] = ['quantity' => 1, 'type' => $type];
        } else {
            $panier[$id]['type'] = $type;
            if ($type === 'pdf' || $type === 'emprunt') {
                $panier[$id]['quantity'] = 1;
            } else {
                $panier[$id]['quantity']++;
            }
        }

        $session->set('panier', $panier);
        
        $this->addFlash('success', 'Ajouté au panier !');
        return $this->redirectToRoute('app_cart_index'); 
    }

    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []); 
        if (!empty($panier[$id])) unset($panier[$id]);
        $session->set('panier', $panier);
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/validate', name: 'app_cart_validate')]
    #[IsGranted('ROLE_USER')]
    public function validate(RequestStack $requestStack, LivreRepository $livreRepository, EntityManagerInterface $em): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);
        $user = $this->getUser();

        if (empty($panier)) return $this->redirectToRoute('app_livre_index');

        // CRÉATION DE LA COMMANDE
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut('VALIDE'); // La commande est payée
        $commande->setTotal(0);
        $em->persist($commande);

        foreach ($panier as $id => $details) {
            $livre = $livreRepository->find($id);
            if (!$livre) continue;

            $type = $details['type'];
            $quantite = $details['quantity'];
            $prixUnitaire = 0;

            // --- 1. ACHAT PHYSIQUE ---
            if ($type === 'achat') {
                if ($livre->getStock() < $quantite) continue;
                $prixUnitaire = $livre->getPrix();
                $livre->setStock($livre->getStock() - $quantite);
            
            // --- 2. ACHAT PDF ---
            } elseif ($type === 'pdf') {
                $prixUnitaire = $livre->getPrixPdf();
            
            // --- 3. EMPRUNT (MODIFIÉ POUR FLUX ADMIN) ---
            } elseif ($type === 'emprunt') {
                if ($livre->getStock() < 1) continue;

                $prixUnitaire = $livre->getPrix() * 0.10; 

                $emprunt = new Emprunt();
                $emprunt->setUser($user);
                $emprunt->setLivre($livre);
                $emprunt->setDateEmprunt(new \DateTime());
                
                // ICI ON CHANGE LA LOGIQUE :
                // On met "null" car c'est l'admin qui déclenchera le chrono de 5 jours
                $emprunt->setDateRetourPrevue(null); 
                
                // On met "EN_ATTENTE" pour que l'admin le voie dans sa liste
                $emprunt->setStatut('EN_ATTENTE'); 
                
                // On initialise l'amende à 0
                $emprunt->setAmende(0);

                // On réserve quand même le stock pour que personne d'autre ne le prenne
                $livre->setStock($livre->getStock() - 1); 
                
                $em->persist($emprunt);
            }

            // --- LIGNE DE COMMANDE ---
            $ligne = new LigneCommande();
            $ligne->setCommande($commande);
            $ligne->setLivre($livre);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire($prixUnitaire);
            $ligne->setType($type); 

            $em->persist($ligne);
            $commande->setTotal($commande->getTotal() + ($prixUnitaire * $quantite));
        }

        $em->flush();
        $session->remove('panier');

        $this->addFlash('success', 'Demande enregistrée ! Vos emprunts sont en attente de validation par le bibliothécaire.');
        return $this->redirectToRoute('app_mes_demandes');
    }
}