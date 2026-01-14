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
        // On récupère les livres triés par ID décroissant (les derniers ajoutés en premier)
        // C'est mieux pour un catalogue dynamique !
        $livres = $livreRepository->findBy([], ['id' => 'DESC']);

        return $this->render('home/index.html.twig', [
            'livres' => $livres,
        ]);
    }
}