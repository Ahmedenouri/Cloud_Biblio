<?php

namespace App\Repository;

use App\Entity\Emprunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emprunt>
 */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }

    /**
     * Compte les emprunts par mois pour l'année en cours
     */
    public function countEmpruntsParMois(): array
    {
        // 1. On récupère TOUS les emprunts de l'année en cours
        // On évite SUBSTRING qui peut échouer selon la config de la BDD
        $emprunts = $this->createQueryBuilder('e')
            ->select('e.dateEmprunt')
            ->where('e.dateEmprunt LIKE :year')
            ->setParameter('year', date('Y') . '-%')
            ->getQuery()
            ->getResult();

        // 2. On prépare un tableau de 12 mois remplis de zéros
        $data = array_fill(0, 12, 0);
        
        // 3. On trie les résultats manuellement en PHP
        foreach ($emprunts as $emprunt) {
            // On extrait le mois de l'objet DateTime ou de la chaîne
            $date = $emprunt['dateEmprunt'];
            
            if ($date instanceof \DateTimeInterface) {
                $monthIndex = (int)$date->format('m') - 1;
            } else {
                // Si c'est une chaîne (ex: "2024-05-12")
                $monthIndex = (int)date('m', strtotime($date)) - 1;
            }

            if ($monthIndex >= 0 && $monthIndex < 12) {
                $data[$monthIndex]++;
            }
        }

        return $data;
    }
}