<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Critere;
use App\Entity\Edition;
use App\Entity\Evaluation;
use App\Entity\NoteCritere;
use App\Enum\StatutDemande;
use App\Enum\StatutTraitement;
use App\Repository\CandidatureRepository;
use App\Repository\CritereRepository;
use App\Repository\EvaluationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/jury", name: "jury_")]
#[IsGranted("ROLE_JURY")]
class JuryController extends AbstractController
{
    public function __construct(
        private readonly CandidatureRepository $candidatureRepository,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route("", name: "index")]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        $candidatures = $this->candidatureRepository->findOneByIdWithEditionAndCandidat();
        $jury = $this->getUser();

        $evaluations = $evaluationRepository->findBy(["jury" => $jury]);
        $evaluationsParCandidature = [];
        foreach ($evaluations as $evaluation) {
            $evaluationsParCandidature[
                (string) $evaluation->getCandidature()->getId()
            ] = $evaluation;
        }

        $totalJurys = $this->getNombreTotalJurys();
        $phaseOraleOuverteParEdition = [];
        foreach ($candidatures as $candidature) {
            $edition = $candidature->getEdition();
            if (!$edition) {
                continue;
            }
            $editionId = $edition->getId();
            if (!isset($phaseOraleOuverteParEdition[$editionId])) {
                $phaseOraleOuverteParEdition[$editionId] = $this->isPhaseEcriteComplete(
                    $edition,
                    $totalJurys,
                );
            }
        }

        return $this->render("jury/index.html.twig", [
            "candidatures" => $candidatures,
            "evaluationsParCandidature" => $evaluationsParCandidature,
            "phaseOraleOuverteParEdition" => $phaseOraleOuverteParEdition,
        ]);
    }

    /**
     * PHASE 1 : notation des critères écrits, PAR jury.
     */
    #[Route("/noter/{id}", name: "noter", methods: ["GET", "POST"])]
    public function noter(
        Candidature $candidature,
        Request $request,
        EntityManagerInterface $em,
        CritereRepository $critereRepository,
        EvaluationRepository $evaluationRepository,
    ): Response {
        $jury = $this->getUser();
        $criteres = $critereRepository->findAllOrdered();
        $criteresFeuilles = $this->flattenCriteresLeaves($criteres);

        $evaluation = $evaluationRepository->findOneBy([
            "jury" => $jury,
            "candidature" => $candidature,
        ]);

        $numeroAnonyme = $this->getNumeroAnonyme($candidature);

        if ($request->isMethod("POST")) {
            $notesPostees = $request->request->all("notes");

            if (!$evaluation) {
                $evaluation = new Evaluation();
                $evaluation->setJury($jury);
                $evaluation->setCandidature($candidature);
                $em->persist($evaluation);
            } else {
                foreach ($evaluation->getNoteCriteres() as $ancienneNote) {
                    $em->remove($ancienneNote);
                }
                $evaluation->getNoteCriteres()->clear();
                $em->flush();
            }

            $erreur = false;

            foreach ($criteresFeuilles as $critere) {
                $brut = $notesPostees[$critere->getId()] ?? null;

                if ($brut === null || $brut === "") {
                    $erreur = true;
                    $this->addFlash(
                        "warning",
                        "⚠️ Merci de renseigner une note pour tous les critères.",
                    );
                    break;
                }

                $valeur = (float) str_replace(",", ".", $brut);

                if ($valeur < 0 || $valeur > $critere->getNoteMax()) {
                    $erreur = true;
                    $this->addFlash(
                        "warning",
                        sprintf(
                            "⚠️ La note pour \"%s\" doit être comprise entre 0 et %s.",
                            $critere->getNom(),
                            $critere->getNoteMax(),
                        ),
                    );
                    break;
                }

                $noteCritere = new NoteCritere();
                $noteCritere->setCritere($critere);
                $noteCritere->setNote($valeur);
                $evaluation->addNoteCritere($noteCritere);
                $em->persist($noteCritere);
            }

            if (!$erreur) {
                $evaluation->setDateEvaluation(new \DateTime());
                $evaluation->calculerNoteEcrite();

                if (
                    $candidature->getStatutTraitement() ===
                    StatutTraitement::EN_ATTENTE
                ) {
                    $candidature->setStatutTraitement(
                        StatutTraitement::EN_COURS_ETUDE,
                    );
                }

                $em->flush();

                $this->recalculerNoteEcriteGenerale($candidature, $em);
                $em->flush();

                $this->addFlash(
                    "success",
                    sprintf(
                        "✅ Notes écrites enregistrées pour le dossier n°%s.",
                        $numeroAnonyme,
                    ),
                );

                return $this->redirectToRoute("jury_index");
            }
        }

        return $this->render("jury/noter.html.twig", [
            "candidature" => $candidature,
            "criteres" => $criteres,
            "evaluation" => $evaluation,
            "numeroAnonyme" => $numeroAnonyme,
        ]);
    }

    /**
     * PHASE 2 : notation orale, PAR jury (comme l'écrit). Accessible une
     * fois que TOUS les jurys ont terminé l'écrit pour TOUS les
     * candidats validés.
     */
    #[Route("/noter-oral/{id}", name: "noter_oral", methods: ["GET", "POST"])]
    public function noterOral(
        Candidature $candidature,
        Request $request,
        EntityManagerInterface $em,
        EvaluationRepository $evaluationRepository,
    ): Response {
        $jury = $this->getUser();
        $numeroAnonyme = $this->getNumeroAnonyme($candidature);

        $evaluation = $evaluationRepository->findOneBy([
            "jury" => $jury,
            "candidature" => $candidature,
        ]);

        if (!$evaluation || $evaluation->getNoteEcrite() === null) {
            $this->addFlash(
                "warning",
                "⚠️ Merci de noter d'abord les critères écrits de ce dossier.",
            );
            return $this->redirectToRoute("jury_noter", [
                "id" => $candidature->getId(),
            ]);
        }

        $edition = $candidature->getEdition();
        $totalJurys = $this->getNombreTotalJurys();

        if ($edition && !$this->isPhaseEcriteComplete($edition, $totalJurys)) {
            $this->addFlash(
                "warning",
                "⚠️ La phase de notation orale n'est pas encore ouverte : certains jurys n'ont pas terminé les notes écrites de tous les dossiers.",
            );
            return $this->redirectToRoute("jury_index");
        }

        if ($request->isMethod("POST")) {
            $noteOraleBrute = $request->request->get("note_orale");

            if ($noteOraleBrute === null || $noteOraleBrute === "") {
                $this->addFlash(
                    "warning",
                    "⚠️ Merci de renseigner la note orale.",
                );
            } else {
                $noteOrale = (float) str_replace(",", ".", $noteOraleBrute);

                if ($noteOrale < 0 || $noteOrale > 20) {
                    $this->addFlash(
                        "warning",
                        "⚠️ La note orale doit être comprise entre 0 et 20.",
                    );
                } else {
                    $evaluation->setNoteOrale($noteOrale);
                    $em->flush();

                    $this->recalculerNoteOraleGenerale($candidature, $em);
                    $em->flush();

                    $this->addFlash(
                        "success",
                        sprintf(
                            "✅ Note orale enregistrée pour le dossier n°%s.",
                            $numeroAnonyme,
                        ),
                    );

                    if ($edition && $this->isPhaseOraleComplete($edition, $totalJurys)) {
                        $this->recalculerPreselection($edition, $em);
                    }

                    return $this->redirectToRoute("jury_index");
                }
            }
        }

        return $this->render("jury/noter_oral.html.twig", [
            "candidature" => $candidature,
            "evaluation" => $evaluation,
            "numeroAnonyme" => $numeroAnonyme,
        ]);
    }

    private function getNumeroAnonyme(Candidature $candidature): ?int
    {
        $toutesCandidatures = $this->candidatureRepository->findOneByIdWithEditionAndCandidat();
        $position = 0;
        foreach ($toutesCandidatures as $c) {
            $position++;
            if ($c->getId() === $candidature->getId()) {
                return $position;
            }
        }
        return null;
    }

    private function flattenCriteresLeaves(array $criteres): array
    {
        $feuilles = [];
        foreach ($criteres as $critere) {
            if ($critere->hasEnfants()) {
                foreach ($critere->getEnfants() as $enfant) {
                    $feuilles[] = $enfant;
                }
            } else {
                $feuilles[] = $critere;
            }
        }
        return $feuilles;
    }

    /**
     * Moyenne des notes écrites de TOUS les jurys pour un candidat.
     */
    private function recalculerNoteEcriteGenerale(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): void {
        $somme = 0.0;
        $count = 0;
        foreach ($candidature->getEvaluations() as $evaluation) {
            if ($evaluation->getNoteEcrite() !== null) {
                $somme += $evaluation->getNoteEcrite();
                $count++;
            }
        }

        if ($count > 0) {
            $candidature->setNoteEcriteGenerale(round($somme / $count, 2));
        }
    }

    /**
     * Moyenne des notes orales de TOUS les jurys pour un candidat
     * (stockée dans Candidature::noteOrale). Recalcule ensuite la note
     * finale (75% écrit général + 25% oral général) si possible.
     */
    private function recalculerNoteOraleGenerale(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): void {
        $somme = 0.0;
        $count = 0;
        foreach ($candidature->getEvaluations() as $evaluation) {
            if ($evaluation->getNoteOrale() !== null) {
                $somme += $evaluation->getNoteOrale();
                $count++;
            }
        }

        if ($count > 0) {
            $noteOraleGenerale = round($somme / $count, 2);
            $candidature->setNoteOrale($noteOraleGenerale);

            if ($candidature->getNoteEcriteGenerale() !== null) {
                $noteFinale = round(
                    0.75 * $candidature->getNoteEcriteGenerale() +
                        0.25 * $noteOraleGenerale,
                    2,
                );
                $candidature->setNote($noteFinale);
            }
        }
    }

    private function getNombreTotalJurys(): int
    {
        return count($this->userRepository->findByRole("ROLE_JURY"));
    }

    private function isPhaseEcriteComplete(Edition $edition, int $totalJurys): bool
    {
        if ($totalJurys === 0) {
            return false;
        }

        $candidaturesValidees = $this->getCandidaturesValidees($edition);
        if (empty($candidaturesValidees)) {
            return false;
        }

        foreach ($candidaturesValidees as $candidature) {
            $count = 0;
            foreach ($candidature->getEvaluations() as $evaluation) {
                if ($evaluation->getNoteEcrite() !== null) {
                    $count++;
                }
            }
            if ($count < $totalJurys) {
                return false;
            }
        }

        return true;
    }

    private function isPhaseOraleComplete(Edition $edition, int $totalJurys): bool
    {
        if ($totalJurys === 0) {
            return false;
        }

        $candidaturesValidees = $this->getCandidaturesValidees($edition);
        if (empty($candidaturesValidees)) {
            return false;
        }

        foreach ($candidaturesValidees as $candidature) {
            $count = 0;
            foreach ($candidature->getEvaluations() as $evaluation) {
                if ($evaluation->getNoteOrale() !== null) {
                    $count++;
                }
            }
            if ($count < $totalJurys) {
                return false;
            }
        }

        return true;
    }

    private function getCandidaturesValidees(Edition $edition): array
    {
        $candidatures = $edition->getCandidatures();
        $candidaturesValidees = [];
        foreach ($candidatures as $candidature) {
            if ($candidature->getStatutDemande() === StatutDemande::VALIDE) {
                $candidaturesValidees[] = $candidature;
            }
        }
        return $candidaturesValidees;
    }

    private function recalculerPreselection(
        Edition $edition,
        EntityManagerInterface $em,
    ): void {
        $candidaturesValidees = $this->getCandidaturesValidees($edition);

        if (empty($candidaturesValidees)) {
            return;
        }

        usort(
            $candidaturesValidees,
            fn(Candidature $a, Candidature $b) => $b->getNote() <=> $a->getNote(),
        );

        $top7Ids = [];
        foreach (array_slice($candidaturesValidees, 0, 7) as $candidature) {
            $top7Ids[] = (string) $candidature->getId();
            $candidature->setStatutTraitement(StatutTraitement::PRESELECTIONNE);
        }

        foreach ($candidaturesValidees as $candidature) {
            $id = (string) $candidature->getId();
            if (!in_array($id, $top7Ids, true)) {
                $candidature->setStatutTraitement(StatutTraitement::NON_RETENU);
            }
        }

        $em->flush();
    }
}