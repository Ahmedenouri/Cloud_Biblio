<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\EmpruntRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        LivreRepository $livreRepo, 
        UserRepository $userRepo, 
        EmpruntRepository $empruntRepo
    ): Response {
        
        // Statistiques globales réelles
        $stats = [
            'countLivres' => $livreRepo->count([]),
            'countUsers' => $userRepo->count([]),
            'countEmprunts' => $empruntRepo->count(['statut' => 'EN_COURS']), 
            'countDemandes' => $empruntRepo->count(['statut' => 'ATTENTE']), 
        ];

        // Ici, on appelle des méthodes personnalisées que vous allez créer dans vos Repository
        // Pour l'instant, ces méthodes renverront des tableaux vides ou calculés
        $chartData = [
            'months' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'],
            'emprunts' => $empruntRepo->countEmpruntsParMois(), // Méthode à créer
            'users' => $userRepo->countInscriptionsParMois(),    // Méthode à créer
        ];

        return $this->render('admin_dashboard/index.html.twig', [
            'stats' => $stats,
            'chartData' => $chartData
        ]);
    }
}