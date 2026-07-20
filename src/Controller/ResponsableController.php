<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Enum\StatutDemande;
use App\Enum\StatutTraitement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/responsable", name: "responsable_")]
#[IsGranted("ROLE_RESPONSABLE")]
class ResponsableController extends AbstractController
{
    #[Route("", name: "index")]
    public function index(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Candidature::class);

        $total = $repo->count([]);
        $valides = $repo->count(["statutDemande" => StatutDemande::VALIDE->value]);
        $rejetes = $repo->count(["statutDemande" => StatutDemande::REJETE->value]);
        $enAttente = $repo->count(["statutDemande" => StatutDemande::SOUMIS->value]);

        $candidatures = $repo->findBy(
            ["statutDemande" => StatutDemande::VALIDE->value],
            ["note" => "DESC"],
        );

        return $this->render("responsable/index.html.twig", [
            "total" => $total,
            "valides" => $valides,
            "rejetes" => $rejetes,
            "enAttente" => $enAttente,
            "candidatures" => $candidatures,
        ]);
    }

    #[Route("/proclamer/{id}", name: "proclamer")]
    public function proclamer(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): Response {
        $repoCandidature = $em->getRepository(Candidature::class);

        $anciensLaureats = $repoCandidature->findBy([
            "statutTraitement" => StatutTraitement::LAUREAT,
            "edition" => $candidature->getEdition(),
        ]);

        foreach ($anciensLaureats as $ancien) {
            $ancien->setStatutTraitement(StatutTraitement::PRESELECTIONNE);
        }

        $candidat = $candidature->getCandidat();

        if ($candidature->getStatutDemande() === StatutDemande::VALIDE) {
            $candidature->setStatutTraitement(StatutTraitement::LAUREAT);
            $em->flush();

            $this->addFlash(
                "success",
                "🏆 " . $candidat->getPrenoms() . " " . $candidat->getNom() .
                    " a été officiellement proclamé lauréat !",
            );
        } else {
            $this->addFlash(
                "danger",
                "Impossible de proclamer une candidature non validée administrativement.",
            );
        }

        return $this->redirectToRoute("responsable_index");
    }

    /**
     * Liste détaillée de toutes les candidatures, toutes éditions confondues,
     * avec leurs notes (écrit, oral, note finale) et leur statut.
     */
    #[Route("/recapitulatif", name: "recapitulatif")]
    public function recapitulatif(EntityManagerInterface $em): Response
    {
        $candidatures = $em->getRepository(Candidature::class)->createQueryBuilder("c")
            ->join("c.candidat", "cand")
            ->addSelect("cand")
            ->leftJoin("c.edition", "e")
            ->addSelect("e")
            ->orderBy("e.annee", "DESC")
            ->addOrderBy("c.note", "DESC")
            ->getQuery()
            ->getResult();

        return $this->render("responsable/recapitulatif.html.twig", [
            "candidatures" => $candidatures,
            "total" => count($candidatures),
        ]);
    }
}