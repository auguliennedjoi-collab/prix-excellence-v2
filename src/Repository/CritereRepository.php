<?php

namespace App\Repository;

use App\Entity\Critere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Critere::class);
    }

    /**
     * Retourne uniquement les critères de niveau supérieur (parent = null),
     * avec leurs enfants éventuels préchargés et ordonnés.
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder("c")
            ->leftJoin("c.enfants", "e")
            ->addSelect("e")
            ->andWhere("c.parent IS NULL")
            ->orderBy("c.ordre", "ASC")
            ->addOrderBy("e.ordre", "ASC")
            ->getQuery()
            ->getResult();
    }
}