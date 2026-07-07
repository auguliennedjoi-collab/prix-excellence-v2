<?php

namespace App\Repository;

use App\Entity\ParametrePage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParametrePageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametrePage::class);
    }

    /**
     * Il n'existe qu'une seule ligne de paramètres pour tout le site.
     * Si elle n'existe pas encore, on la crée avec des valeurs par défaut valides.
     */
    public function getOrCreate(): ParametrePage
    {
        $parametre = $this->findOneBy([]);

        if (!$parametre) {
            $parametre = new ParametrePage();

            // Initialisation des plages de dates (Dates par défaut : aujourd'hui)
            $maintenant = new \DateTime();

            $parametre->setDateDebutEtude($maintenant);
            $parametre->setDateFinEtude($maintenant);

            $parametre->setDateDebutPreselection($maintenant);
            $parametre->setDateFinPreselection($maintenant);

            $parametre->setDateDebutAudition($maintenant);
            $parametre->setDateFinAudition($maintenant);

            $parametre->setDateDebutProclamation($maintenant);
            $parametre->setDateFinProclamation($maintenant);

            // Texte brut pour le champ TEXT
            $parametre->setQuiPeutParticiper(
                "Jeunes chercheurs, magistrats, avocats, doctorants, enseignants-chercheurs et professionnels des sciences juridiques et sociales. Diplôme Master minimum requis.",
            );

            // Tableaux PHP pour les colonnes de type JSON
            $parametre->setDossierRequis([
                "Contribution scientifique (25-35 pages)",
                "Résumé (1 page max)",
                "Curriculum Vitae",
                "Copie légalisée du diplôme",
                "Pièce d'identité",
            ]);

            $parametre->setRecompenses([
                "Distinction officielle",
                "Attestation d'excellence",
                "Récompense financière",
                "Publication de la contribution",
            ]);

            // Texte du pied de page et tracking de mise à jour
            $parametre->setFooterTexte(
                "© 2026 Cour Suprême du Bénin — Tous droits réservés",
            );
            $parametre->setUpdatedAt(new \DateTimeImmutable());

            $em = $this->getEntityManager();
            $em->persist($parametre);
            $em->flush();
        }

        return $parametre;
    }
}
