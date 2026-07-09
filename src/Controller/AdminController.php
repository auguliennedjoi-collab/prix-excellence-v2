<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Critere;
use App\Entity\Edition;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Enum\StatutTraitement;
use App\Form\ParametrePageType;
use App\Repository\CritereRepository;
use App\Repository\ParametrePageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route("/admin", name: "admin_")]
#[IsGranted("ROLE_ADMIN")]
class AdminController extends AbstractController
{
    #[Route("", name: "index")]
    public function index(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Candidature::class);

        $total = $repo->count([]);
        $enAttente = $repo->count(["statutDemande" => StatutDemande::SOUMIS]);
        $valides = $repo->count(["statutDemande" => StatutDemande::VALIDE]);
        $rejetes = $repo->count(["statutDemande" => StatutDemande::REJETE]);

        return $this->render("admin/index.html.twig", [
            "total" => $total,
            "enAttente" => $enAttente,
            "valides" => $valides,
            "rejetes" => $rejetes,
        ]);
    }

  #[Route("/candidats", name: "candidats")]
public function candidats(
    Request $request,
    EntityManagerInterface $em,
): Response {
    $page = $request->query->getInt("page", 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $statut = $request->query->get("statut");
    $recherche = trim($request->query->get("recherche", ""));

    $repo = $em->getRepository(Candidature::class);
    $qb = $repo->createQueryBuilder("c")
        ->join("c.candidat", "cand");

    if ($statut === "soumis") {
        $qb->andWhere("c.statutDemande = :statut")->setParameter("statut", StatutDemande::SOUMIS);
    } elseif ($statut === "valide") {
        $qb->andWhere("c.statutDemande = :statut")->setParameter("statut", StatutDemande::VALIDE);
    } elseif ($statut === "rejete") {
        $qb->andWhere("c.statutDemande = :statut")->setParameter("statut", StatutDemande::REJETE);
    }

    if ($recherche !== "") {
        $qb->andWhere("cand.nom LIKE :recherche OR cand.prenoms LIKE :recherche OR cand.email LIKE :recherche OR c.codeSuivi LIKE :recherche")
            ->setParameter("recherche", "%" . $recherche . "%");
    }

    $total = (clone $qb)->select("COUNT(c.id)")->getQuery()->getSingleScalarResult();

    $candidatures = $qb->orderBy("c.dateSoumission", "DESC")
        ->setFirstResult($offset)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();

    $totalPages = (int) ceil($total / $limit);

    return $this->render("admin/candidats.html.twig", [
        "candidatures" => $candidatures,
        "page" => $page,
        "totalPages" => $totalPages,
        "statut" => $statut,
        "recherche" => $recherche,
    ]);
}

    #[Route("/candidat/{id}/valider", name: "valider")]
    public function valider(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): Response {
        if (!$candidature) {
            $this->addFlash("danger", "❌ Candidature introuvable.");
            return $this->redirectToRoute("admin_candidats");
        }

        $candidature->setStatutDemande(StatutDemande::VALIDE);
        $candidature->setStatutTraitement(StatutTraitement::EN_ATTENTE);


        $em->flush();

        $candidat = $candidature->getCandidat();
        $this->addFlash(
            "success",
            "✅ La candidature de " .
                $candidat->getPrenoms() .
                " " .
                $candidat->getNom() .
                " a été validée administrativement "
        );

        return $this->redirectToRoute("admin_candidats");
    }

    #[Route("/candidat/{id}/rejeter", name: "rejeter")]
    public function rejeter(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): Response {
        if (!$candidature) {
            $this->addFlash("danger", "❌ Candidature introuvable.");
            return $this->redirectToRoute("admin_candidats");
        }

        $candidature->setStatutDemande(StatutDemande::REJETE);
        $candidature->setStatutTraitement(StatutTraitement::NON_RETENU);
        $em->flush();

        $candidat = $candidature->getCandidat();
        $this->addFlash(
            "danger",
            "❌ La candidature de " .
                $candidat->getPrenoms() .
                " " .
                $candidat->getNom() .
                " a été rejetée.",
        );

        return $this->redirectToRoute("admin_candidats");
    }

    #[Route("/utilisateurs", name: "utilisateurs")]
    public function utilisateursList(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->render("admin/utilisateurs.html.twig", [
            "users" => $users,
        ]);
    }

    #[
        Route(
            "/utilisateurs/ajouter",
            name: "ajouter_utilisateur",
            methods: ["POST"],
        ),
    ]
    public function ajouterUtilisateur(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $user = new User();
        $user->setNom($request->request->get("nom"));
        $user->setPrenoms($request->request->get("prenoms"));
        $user->setEmail($request->request->get("email"));
        $user->setTelephone($request->request->get("telephone"));
        $user->setTitre($request->request->get("titre"));

        $role = $request->request->get("role");
        $user->setRoles([$role]);

        $plainPassword = $request->request->get("mot_de_passe");
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $em->persist($user);
        $em->flush();

        $this->addFlash(
            "success",
            "✅ Utilisateur " . $user->getPrenoms() . " ajouté avec succès !",
        );
        return $this->redirectToRoute("admin_utilisateurs");
    }

    #[
        Route(
            "/utilisateurs/{id}/modifier",
            name: "modifier_utilisateur",
            methods: ["GET", "POST"],
        ),
    ]
    public function modifierUtilisateur(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        if (!$user) {
            $this->addFlash("danger", "❌ Utilisateur introuvable.");
            return $this->redirectToRoute("admin_utilisateurs");
        }

        if ($request->isMethod("POST")) {
            $user->setNom($request->request->get("nom"));
            $user->setPrenoms($request->request->get("prenoms"));
            $user->setEmail($request->request->get("email"));
            $user->setTelephone($request->request->get("telephone"));
            $user->setTitre($request->request->get("titre"));

            $role = $request->request->get("role");
            $user->setRoles([$role]);

            $mdp = $request->request->get("mot_de_passe");
            if ($mdp && $mdp !== "") {
                $user->setPassword($hasher->hashPassword($user, $mdp));
            }

            $em->flush();
            $this->addFlash("success", "✅ Utilisateur modifié avec succès !");
            return $this->redirectToRoute("admin_utilisateurs");
        }

        return $this->render("admin/modifier_utilisateur.html.twig", [
            "user" => $user,
        ]);
    }

    #[Route("/utilisateurs/{id}/supprimer", name: "supprimer_utilisateur")]
    public function supprimerUtilisateur(
        User $user,
        EntityManagerInterface $em,
    ): Response {
        if ($user) {
            $nomComplet = $user->getPrenoms() . " " . $user->getNom();
            $em->remove($user);
            $em->flush();
            $this->addFlash(
                "warning",
                "🗑️ Utilisateur " . $nomComplet . " supprimé.",
            );
        }

        return $this->redirectToRoute("admin_utilisateurs");
    }

    #[Route("/parametres", name: "parametres")]
    public function parametres(
        Request $request,
        ParametrePageRepository $repository,
        EntityManagerInterface $em,
    ): Response {
        $parametre = $repository->getOrCreate();

        $form = $this->createForm(ParametrePageType::class, $parametre, [
            "csrf_protection" => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parametre->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($parametre);
            $em->flush();

            $this->addFlash(
                "success",
                "✅ Les paramètres ont été mis à jour avec succès.",
            );
            return $this->redirectToRoute("admin_parametres");
        }

        return $this->render("admin/parametres.html.twig", [
            "form" => $form->createView(),
            "parametre" => $parametre,
        ]);
    }

    #[Route("/criteres", name: "criteres")]
    public function criteres(CritereRepository $critereRepository): Response
    {
        $criteres = $critereRepository->findAllOrdered();

        return $this->render("admin/criteres.html.twig", [
            "criteres" => $criteres,
        ]);
    }

    #[Route("/criteres/ajouter", name: "ajouter_critere", methods: ["POST"])]
    public function ajouterCritere(
        Request $request,
        EntityManagerInterface $em,
        CritereRepository $critereRepository,
    ): Response {
        $nom = trim($request->request->get("nom"));
        $noteMax = $request->request->get("note_max");
        $description = $request->request->get("description");
        $parentId = $request->request->get("parent_id");

        if ($nom === "" || $noteMax === null || $noteMax === "") {
            $this->addFlash(
                "warning",
                "⚠️ Le nom et la note maximale sont obligatoires.",
            );
            return $this->redirectToRoute("admin_criteres");
        }

        $critere = new Critere();
        $critere->setNom($nom);
        $critere->setNoteMax((float) str_replace(",", ".", $noteMax));
        $critere->setDescription($description !== "" ? $description : null);

        $parent = null;
        if ($parentId) {
            $parent = $critereRepository->find($parentId);
            if (!$parent) {
                $this->addFlash("danger", "❌ Critère parent introuvable.");
                return $this->redirectToRoute("admin_criteres");
            }
            $ordre = count($parent->getEnfants()) + 1;
            $critere->setOrdre($ordre);
            $critere->setParent($parent);
        } else {
            $tousLesParents = $critereRepository->findBy(["parent" => null]);
            $ordre = count($tousLesParents) + 1;
            $critere->setOrdre($ordre);
        }

        $em->persist($critere);
        $em->flush();

        if ($parent) {
            $this->recalculerNoteMaxParent($parent, $em);
        }

        $this->addFlash(
            "success",
            "✅ Critère \"" . $critere->getNom() . "\" ajouté avec succès.",
        );

        return $this->redirectToRoute("admin_criteres");
    }

    #[
        Route(
            "/criteres/{id}/modifier",
            name: "modifier_critere",
            methods: ["POST"],
        ),
    ]
    public function modifierCritere(
        Critere $critere,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $nom = trim($request->request->get("nom"));
        $noteMax = $request->request->get("note_max");
        $description = $request->request->get("description");

        if ($nom === "" || $noteMax === null || $noteMax === "") {
            $this->addFlash(
                "warning",
                "⚠️ Le nom et la note maximale sont obligatoires.",
            );
            return $this->redirectToRoute("admin_criteres");
        }

        $critere->setNom($nom);
        $critere->setNoteMax((float) str_replace(",", ".", $noteMax));
        $critere->setDescription($description !== "" ? $description : null);

        $em->flush();

        $parent = $critere->getParent();
        if ($parent) {
            $this->recalculerNoteMaxParent($parent, $em);
        }

        $this->addFlash(
            "success",
            "✅ Critère \"" . $critere->getNom() . "\" modifié avec succès.",
        );

        return $this->redirectToRoute("admin_criteres");
    }

    #[Route("/criteres/{id}/supprimer", name: "supprimer_critere")]
    public function supprimerCritere(
        Critere $critere,
        EntityManagerInterface $em,
    ): Response {
        $nom = $critere->getNom();

        $parent = $critere->getParent();

        foreach ($critere->getEnfants() as $enfant) {
            $em->remove($enfant);
        }

        $em->remove($critere);
        $em->flush();

        if ($parent) {
            $this->recalculerNoteMaxParent($parent, $em);
        }

        $this->addFlash(
            "warning",
            "🗑️ Critère \"" . $nom . "\" supprimé.",
        );

        return $this->redirectToRoute("admin_criteres");
    }

    /**
     * Met à jour la note max d'un critère parent pour qu'elle corresponde
     * à la somme des notes max de tous ses enfants directs.
     */
    private function recalculerNoteMaxParent(
        Critere $parent,
        EntityManagerInterface $em,
    ): void {
        $sommeEnfants = 0.0;
        foreach ($parent->getEnfants() as $enfant) {
            $sommeEnfants += $enfant->getNoteMax();
        }

        $parent->setNoteMax($sommeEnfants);
        $em->flush();

        $grandParent = $parent->getParent();
        if ($grandParent) {
            $this->recalculerNoteMaxParent($grandParent, $em);
        }
    }

    // ==========================================================
    // GESTION DU PLAGIAT (top 7 présélectionnés -> 5 finalistes)
    // ==========================================================

    /**
     * Page listant le pool de candidats actuellement présélectionnés
     * (statut PRESELECTIONNE), pour que l'admin y renseigne le taux de
     * plagiat et élimine ceux jugés trop suspects. Liste aussi
     * l'historique des candidats déjà éliminés pour plagiat.
     */
    #[Route("/plagiat", name: "plagiat")]
    public function plagiat(EntityManagerInterface $em): Response
    {
        $edition = $this->getEditionAvecPreselection($em);

        if (!$edition) {
            return $this->render("admin/plagiat.html.twig", [
                "edition" => null,
                "pool" => [],
                "elimines" => [],
            ]);
        }

        $pool = $this->getCandidaturesParStatut(
            $edition,
            StatutTraitement::PRESELECTIONNE,
        );
        usort(
            $pool,
            fn(Candidature $a, Candidature $b) => $b->getNote() <=> $a->getNote(),
        );

        $elimines = $this->getCandidaturesParStatut(
            $edition,
            StatutTraitement::ELIMINE_PLAGIAT,
        );

        return $this->render("admin/plagiat.html.twig", [
            "edition" => $edition,
            "pool" => $pool,
            "elimines" => $elimines,
        ]);
    }

    /**
     * Enregistre le taux de plagiat constaté par l'admin pour un candidat,
     * sans forcément l'éliminer (décision manuelle séparée).
     */
    #[Route("/plagiat/{id}/taux", name: "plagiat_taux", methods: ["POST"])]
    public function definirTauxPlagiat(
        Candidature $candidature,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $tauxBrut = $request->request->get("taux_plagiat");

        if ($tauxBrut === null || $tauxBrut === "") {
            $this->addFlash(
                "warning",
                "⚠️ Merci de renseigner un taux de plagiat.",
            );
            return $this->redirectToRoute("admin_plagiat");
        }

        $taux = (float) str_replace(",", ".", $tauxBrut);

        if ($taux < 0 || $taux > 100) {
            $this->addFlash(
                "warning",
                "⚠️ Le taux de plagiat doit être compris entre 0 et 100.",
            );
            return $this->redirectToRoute("admin_plagiat");
        }

        $candidature->setTauxPlagiat($taux);
        $em->flush();

        $this->addFlash(
            "success",
            "✅ Taux de plagiat enregistré (" . $taux . "%).",
        );

        return $this->redirectToRoute("admin_plagiat");
    }

    /**
     * Élimine un candidat pour plagiat. Le système va alors chercher
     * automatiquement le(s) candidat(s) suivant(s) du classement général
     * (parmi les NON_RETENU) pour compléter la liste jusqu'à obtenir
     * à nouveau 5 finalistes actifs.
     */
    #[Route("/plagiat/{id}/eliminer", name: "plagiat_eliminer")]
    public function eliminerPourPlagiat(
        Candidature $candidature,
        EntityManagerInterface $em,
    ): Response {
        $edition = $candidature->getEdition();

        $candidature->setStatutTraitement(StatutTraitement::ELIMINE_PLAGIAT);
        $em->flush();

        $candidat = $candidature->getCandidat();
        $this->addFlash(
            "danger",
            "❌ " . $candidat->getPrenoms() . " " . $candidat->getNom() .
                " a été éliminé(e) pour plagiat.",
        );

        if ($edition) {
            $this->completerPoolFinalistes($edition, $em);
        }

        return $this->redirectToRoute("admin_plagiat");
    }

    private function getEditionAvecPreselection(
        EntityManagerInterface $em,
    ): ?Edition {
        $candidature = $em
            ->getRepository(Candidature::class)
            ->findOneBy(["statutTraitement" => StatutTraitement::PRESELECTIONNE]);

        return $candidature?->getEdition();
    }

    /**
     * @return Candidature[]
     */
    private function getCandidaturesParStatut(
        Edition $edition,
        StatutTraitement $statut,
    ): array {
        $resultat = [];
        foreach ($edition->getCandidatures() as $candidature) {
            if ($candidature->getStatutTraitement() === $statut) {
                $resultat[] = $candidature;
            }
        }
        return $resultat;
    }

    /**
     * Cible : toujours 5 finalistes actifs (statut PRESELECTIONNE) dans
     * le pool. Si une élimination fait descendre ce nombre en dessous de
     * 5, on repioche automatiquement les mieux classés parmi les
     * NON_RETENU pour compléter.
     */
    private function completerPoolFinalistes(
        Edition $edition,
        EntityManagerInterface $em,
    ): void {
        $cible = 5;

        $preselectionnesActuels = $this->getCandidaturesParStatut(
            $edition,
            StatutTraitement::PRESELECTIONNE,
        );

        $nombreActuel = count($preselectionnesActuels);

        if ($nombreActuel >= $cible) {
            return;
        }

        $manquants = $cible - $nombreActuel;

        $candidatsRestants = $this->getCandidaturesParStatut(
            $edition,
            StatutTraitement::NON_RETENU,
        );

        usort(
            $candidatsRestants,
            fn(Candidature $a, Candidature $b) => $b->getNote() <=> $a->getNote(),
        );

        $aAjouter = array_slice($candidatsRestants, 0, $manquants);

        foreach ($aAjouter as $candidature) {
            $candidature->setStatutTraitement(StatutTraitement::PRESELECTIONNE);
        }

        if (!empty($aAjouter)) {
            $em->flush();

            $noms = array_map(
                fn(Candidature $c) => $c->getCandidat()->getPrenoms() . " " . $c->getCandidat()->getNom(),
                $aAjouter,
            );
            $this->addFlash(
                "info",
                "ℹ️ Complété automatiquement avec : " . implode(", ", $noms) . ".",
            );
        }
    }
}