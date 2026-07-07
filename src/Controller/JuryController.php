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

        return $this->render("jury/index.html.twig", [
            "candidatures" => $candidatures,
            "evaluationsParCandidature" => $evaluationsParCandidature,
        ]);
    }

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
            $noteOraleBrute = $request->request->get("note_orale");

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
                if ($noteOraleBrute === null || $noteOraleBrute === "") {
                    $erreur = true;
                    $this->addFlash(
                        "warning",
                        "⚠️ Merci de renseigner la note orale.",
                    );
                } else {
                    $noteOrale = (float) str_replace(",", ".", $noteOraleBrute);
                    if ($noteOrale < 0 || $noteOrale > 20) {
                        $erreur = true;
                        $this->addFlash(
                            "warning",
                            "⚠️ La note orale doit être comprise entre 0 et 20.",
                        );
                    } else {
                        $evaluation->setNoteOrale($noteOrale);
                    }
                }
            }

            if (!$erreur) {
                $evaluation->setDateEvaluation(new \DateTime());
                $evaluation->calculerNoteFinale();

                if (
                    $candidature->getStatutTraitement() ===
                    StatutTraitement::EN_ATTENTE
                ) {
                    $candidature->setStatutTraitement(
                        StatutTraitement::EN_COURS_ETUDE,
                    );
                }

                $em->flush();

                $this->recalculerMoyenneCandidature($candidature, $em);
                $em->flush();

                $this->addFlash(
                    "success",
                    sprintf(
                        "✅ Notes enregistrées pour le dossier n°%s. Merci d'indiquer maintenant le taux de plagiat perçu.",
                        $numeroAnonyme,
                    ),
                );

                return $this->redirectToRoute("jury_plagiat", [
                    "id" => $candidature->getId(),
                ]);
            }
        }

        return $this->render("jury/noter.html.twig", [
            "candidature" => $candidature,
            "criteres" => $criteres,
            "evaluation" => $evaluation,
            "numeroAnonyme" => $numeroAnonyme,
        ]);
    }

    #[Route("/plagiat/{id}", name: "plagiat", methods: ["GET", "POST"])]
    public function plagiat(
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

        if (!$evaluation) {
            $this->addFlash(
                "warning",
                "⚠️ Merci de noter d'abord les critères écrits et la note orale.",
            );
            return $this->redirectToRoute("jury_noter", [
                "id" => $candidature->getId(),
            ]);
        }

        if ($request->isMethod("POST")) {
            $tauxPlagiatBrut = $request->request->get("taux_plagiat");

            if ($tauxPlagiatBrut === null || $tauxPlagiatBrut === "") {
                $this->addFlash(
                    "warning",
                    "⚠️ Merci de renseigner le taux de plagiat perçu.",
                );
            } else {
                $tauxPlagiat = (float) str_replace(",", ".", $tauxPlagiatBrut);

                if ($tauxPlagiat < 0 || $tauxPlagiat > 100) {
                    $this->addFlash(
                        "warning",
                        "⚠️ Le taux de plagiat doit être compris entre 0 et 100.",
                    );
                } else {
                    $evaluation->setTauxPlagiat($tauxPlagiat);
                    $em->flush();

                    $edition = $candidature->getEdition();
                    if ($edition) {
                        $this->recalculerElimination($edition, $em);
                        $this->recalculerPreselection($edition, $em);
                    }

                    $this->addFlash(
                        "success",
                        sprintf(
                            "✅ Taux de plagiat enregistré pour le dossier n°%s. Évaluation complète.",
                            $numeroAnonyme,
                        ),
                    );

                    return $this->redirectToRoute("jury_index");
                }
            }
        }

        return $this->render("jury/plagiat.html.twig", [
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

    private function recalculerMoyenneCandidature(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): void {
        $evaluations = $candidature->getEvaluations();

        if (count($evaluations) === 0) {
            $candidature->setNote(null);
            return;
        }

        $somme = 0.0;
        $count = 0;
        foreach ($evaluations as $evaluation) {
            if ($evaluation->getNoteFinale() !== null) {
                $somme += $evaluation->getNoteFinale();
                $count++;
            }
        }

        if ($count > 0) {
            $candidature->setNote(round($somme / $count, 2));
        }
    }

    private function recalculerElimination(
        Edition $edition,
        EntityManagerInterface $em,
    ): void {
        $candidatures = $edition->getCandidatures();

        $moyennesParCandidature = [];

        foreach ($candidatures as $candidature) {
            $evaluations = $candidature->getEvaluations();
            $somme = 0.0;
            $count = 0;

            foreach ($evaluations as $evaluation) {
                if ($evaluation->getTauxPlagiat() !== null) {
                    $somme += $evaluation->getTauxPlagiat();
                    $count++;
                }
            }

            if ($count > 0) {
                $moyennesParCandidature[(string) $candidature->getId()] = round($somme / $count, 2);
            }
        }

        if (empty($moyennesParCandidature)) {
            return;
        }

        $tauxMax = max($moyennesParCandidature);

        foreach ($candidatures as $candidature) {
            $id = (string) $candidature->getId();

            if (!isset($moyennesParCandidature[$id])) {
                continue;
            }

            if ($moyennesParCandidature[$id] === $tauxMax && $tauxMax > 0) {
                $candidature->setStatutTraitement(StatutTraitement::ELIMINE_PLAGIAT);
            } elseif (
                $candidature->getStatutTraitement() === StatutTraitement::ELIMINE_PLAGIAT
            ) {
                $candidature->setStatutTraitement(StatutTraitement::EN_COURS_ETUDE);
            }
        }

        $em->flush();
    }

    /**
     * Présélectionne automatiquement les 7 meilleures moyennes d'une édition
     * (hors candidats déjà éliminés pour plagiat), une fois que toutes les
     * candidatures validées de l'édition ont été notées.
     */
    private function recalculerPreselection(
        Edition $edition,
        EntityManagerInterface $em,
    ): void {
        $candidatures = $edition->getCandidatures();

        $candidaturesValidees = [];
        foreach ($candidatures as $candidature) {
            if ($candidature->getStatutDemande() === StatutDemande::VALIDE) {
                $candidaturesValidees[] = $candidature;
            }
        }

        if (empty($candidaturesValidees)) {
            return;
        }

        // On attend que toutes les candidatures validées aient une note
        // (donc que l'ensemble du jury ait terminé son travail)
        foreach ($candidaturesValidees as $candidature) {
            if ($candidature->getNote() === null) {
                return; // Pas encore prêt : au moins une candidature n'a pas de note
            }
        }

        // On exclut les candidats déjà éliminés pour plagiat du classement
        $classables = array_filter(
            $candidaturesValidees,
            fn(Candidature $c) => $c->getStatutTraitement() !== StatutTraitement::ELIMINE_PLAGIAT,
        );

        usort($classables, fn(Candidature $a, Candidature $b) => $b->getNote() <=> $a->getNote());

        $top7Ids = [];
        foreach (array_slice($classables, 0, 7) as $candidature) {
            $top7Ids[] = (string) $candidature->getId();
            $candidature->setStatutTraitement(StatutTraitement::PRESELECTIONNE);
        }

        foreach ($classables as $candidature) {
            $id = (string) $candidature->getId();
            if (!in_array($id, $top7Ids, true)) {
                $candidature->setStatutTraitement(StatutTraitement::NON_RETENU);
            }
        }

        $em->flush();
    }
}