<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Critere;
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

        // Détermine l'ordre : dernier de son niveau + 1
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

        // Supprime aussi les enfants (cascade déjà gérée par onDelete: CASCADE
        // sur la relation, mais on le fait explicitement pour être sûr
        // que Doctrine nettoie correctement l'UnitOfWork)
        foreach ($critere->getEnfants() as $enfant) {
            $em->remove($enfant);
        }

        $em->remove($critere);
        $em->flush();

        $this->addFlash(
            "warning",
            "🗑️ Critère \"" . $nom . "\" supprimé.",
        );

        return $this->redirectToRoute("admin_criteres");
    }
}