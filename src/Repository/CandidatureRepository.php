<?php

namespace App\Repository;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\Edition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Uuid;

/**
 * @extends ServiceEntityRepository<Candidature>
 */
class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    /**
     * @return Candidature|null Returns a single Candidature object or null if not found
     */
    public function findByEditionAndUser(
        Edition $edition,
        string $nom,
        string $prenoms,
    ): ?Candidature {
        return $this->createQueryBuilder("c")
            ->leftJoin(
                "c.candidat",
                "candidat",
                "WITH",
                "candidat.nom = :nom AND candidat.prenoms = :prenoms",
            )
            ->leftJoin("c.edition", "edition", "WITH", "edition.id = :edition")
            ->setParameter("edition", $edition)
            ->setParameter("nom", $nom)
            ->setParameter("prenoms", $prenoms)
            ->orderBy("c.id", "ASC")
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByCodeSuivi(string $value): ?Candidature
    {
        return $this->createQueryBuilder("c")
            ->leftJoin("c.candidat", "candidat")
            ->andWhere("c.codeSuivi = :val")
            ->setParameter("val", $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Description
     *
     * @param Uuid $id
     * @return Candidature[] |null
     */
    public function findOneByIdWithEditionAndCandidat()
    {
        return $this->createQueryBuilder("c")
            ->leftJoin("c.candidat", "candidat")
            ->leftJoin("c.edition", "edition")
            ->leftJoin("c.document", "document")
            ->addSelect("candidat", "edition", "document")
            ->getQuery()
            ->getResult();
    }
}
