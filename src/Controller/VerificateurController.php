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

#[Route("/verificateur", name: "verificateur_")]
#[IsGranted("ROLE_VERIFICATEUR")]
class VerificateurController extends AbstractController
{
    #[Route("", name: "index")]
    public function index(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Candidature::class);

        // On compte en se basant sur les valeurs de l'Enum StatutDemande
        $total = $repo->count([]);

        $valides = $repo->count([
            "statutDemande" => StatutDemande::VALIDE->value,
        ]);
        $rejetes = $repo->count([
            "statutDemande" => StatutDemande::REJETE->value,
        ]);

        // En attente correspond au statut "SOUMIS" initialement
        $enAttente = $repo->count([
            "statutDemande" => StatutDemande::SOUMIS->value,
        ]);

        // On récupère uniquement les candidats validés administrativement pour l'évaluation
        $candidatures = $repo->findBy(
            ["statutDemande" => StatutDemande::VALIDE->value],
            ["note" => "DESC"],
        );

        return $this->render("verificateur/index.html.twig", [
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

        // 2. Récupérer l'objet Candidat associé pour avoir son identité
        $candidat = $candidature->getCandidat();

        // 3. Proclamer le nouveau lauréat si la candidature est validée administrativement
        if ($candidature->getStatutDemande() === StatutDemande::VALIDE) {
            $candidature->setStatutTraitement(StatutTraitement::LAUREAT);
            $em->flush();

            $this->addFlash(
                "success",
                "🏆 " .
                    $candidat->getPrenoms() .
                    " " .
                    $candidat->getNom() .
                    " a été officiellement proclamé lauréat !",
            );
        } else {
            $this->addFlash(
                "danger",
                "Impossible de proclamer une candidature non validée administrativement.",
            );
        }

        return $this->redirectToRoute("verificateur_index");
    }
}
