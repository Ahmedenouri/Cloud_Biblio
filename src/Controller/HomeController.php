<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(LivreRepository $livreRepository): Response
    {
        // Récupère tous les livres pour les afficher sur la page d'accueil
        return $this->render('home/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }
}