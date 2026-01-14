<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Compte les nouvelles inscriptions par mois pour l'année en cours
     * @return array
     */
    public function countInscriptionsParMois(): array
    {
        // On récupère les inscriptions groupées par mois
        // Note: Si votre champ n'est pas 'createdAt', remplacez-le ci-dessous
        $results = $this->createQueryBuilder('u')
            ->select('SUBSTRING(u.createdAt, 6, 2) as mois, COUNT(u.id) as total')
            ->where('u.createdAt LIKE :year')
            ->setParameter('year', date('Y') . '-%')
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_fill(0, 12, 0);
        
        foreach ($results as $result) {
            $index = (int)$result['mois'] - 1;
            if ($index >= 0 && $index < 12) {
                $data[$index] = (int)$result['total'];
            }
        }

        return $data;
    }
}