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
                $dataPanier[] = [
                    'livre' => $livre,
                    'quantity' => $details['quantity'],
                    'type' => $details['type']
                ];
                
                // CALCUL DU TOTAL AVEC GESTION DU PRIX PDF
                if ($details['type'] === 'achat') {
                    $totalAchat += $livre->getPrix() * $details['quantity'];
                } 
                elseif ($details['type'] === 'pdf') {
                    // ICI : On utilise le prix réduit (-30%)
                    $totalAchat += $livre->getPrixPdf() * $details['quantity'];
                }
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
        
        // On récupère le type : 'emprunt', 'achat', ou 'pdf'
        $type = $request->query->get('type', 'emprunt'); 

        if (empty($panier[$id])) {
            $panier[$id] = [
                'quantity' => 1,
                'type' => $type
            ];
        } else {
            // Mise à jour du type
            $panier[$id]['type'] = $type;

            // On n'achète généralement qu'un seul exemplaire d'un PDF
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

        $commande = null;

        foreach ($panier as $id => $details) {
            $livre = $livreRepository->find($id);
            if (!$livre) continue;

            // --- CAS 1: EMPRUNT ---
            if ($details['type'] === 'emprunt') {
                if ($livre->getStock() > 0) {
                    $emprunt = new Emprunt();
                    $emprunt->setUser($user);
                    $emprunt->setLivre($livre);
                    $emprunt->setDateEmprunt(new \DateTime());
                    $emprunt->setDateRetourPrevue((new \DateTime())->modify('+15 days'));
                    $emprunt->setStatut('EN_ATTENTE');
                    $livre->setStock($livre->getStock() - 1); 
                    $em->persist($emprunt);
                }
            } 
            
            // --- CAS 2: ACHAT (Physique) OU PDF (Numérique) ---
            elseif ($details['type'] === 'achat' || $details['type'] === 'pdf') {
                
                // Si c'est physique, on vérifie le stock. Si c'est PDF, on s'en fiche.
                $stockSuffisant = ($details['type'] === 'pdf') ? true : ($livre->getStock() >= $details['quantity']);

                if ($stockSuffisant) {
                    if (!$commande) {
                        $commande = new Commande();
                        $commande->setUser($user);
                        $commande->setDateCommande(new \DateTime());
                        $commande->setStatut('VALIDE'); // Validé direct
                        $commande->setTotal(0);
                        $em->persist($commande);
                    }

                    // DÉTERMINATION DU PRIX UNITAIRE (Normal ou Réduit)
                    $prixUnitaire = ($details['type'] === 'pdf') ? $livre->getPrixPdf() : $livre->getPrix();

                    $ligne = new LigneCommande();
                    $ligne->setCommande($commande);
                    $ligne->setLivre($livre);
                    $ligne->setQuantite($details['quantity']);
                    $ligne->setPrixUnitaire($prixUnitaire); // On utilise le prix calculé
                    
                    // --- AJOUT IMPORTANT : ON SAUVEGARDE LE TYPE ('achat' ou 'pdf') ---
                    $ligne->setType($details['type']);
                    // ------------------------------------------------------------------

                    // ON NE DÉCRÉMENTE LE STOCK QUE SI C'EST PHYSIQUE
                    if ($details['type'] === 'achat') {
                        $livre->setStock($livre->getStock() - $details['quantity']);
                    }

                    $em->persist($ligne);
                    $commande->setTotal($commande->getTotal() + ($prixUnitaire * $details['quantity']));
                }
            }
        }

        $em->flush();
        $session->remove('panier');

        $this->addFlash('success', 'Commandes validées ! Retrouvez vos livres dans "Mes Demandes".');
        return $this->redirectToRoute('app_mes_demandes');
    }
}