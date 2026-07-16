<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\Document;
use App\Enum\StatutDemande;
use App\Enum\StatutTraitement;
use App\Event\CandidatureAccepteEvent;
use App\Form\CandidatType;
use App\Form\SuiviType;
use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\EditionRepository;
use App\Repository\ParametrePageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CandidatureController extends AbstractController
{
    public function __construct(
        private readonly EditionRepository $editionRepository,
        private readonly CandidatRepository $candidatRepository,
        private readonly CandidatureRepository $candidatureRepository,
        private readonly ParametrePageRepository $parametreRepository,
        #[
            Autowire("%uploads_directory%"),
        ]
        private readonly string $uploadsDirectory,
    ) {}

    #[Route("/", name: "app_home")]
    public function index(): Response
    {
        $edition = $this->editionRepository->findOneBy(["annee" => date("Y")]);
        $parametre = $this->parametreRepository->findOneBy([]);
        return $this->render("home/index.html.twig", [
            "edition" => $edition,
            "parametre" => $parametre,
        ]);
    }

    #[
        Route(
            "/postuler",
            name: "app_candidature_postuler",
            methods: ["GET", "POST"],
        ),
    ]
    public function postuler(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        // Vérification des dates de l'édition
        $edition = $this->editionRepository->findOneBy(["annee" => date("Y")]);
        $now = new \DateTime();

        if (!$edition || $now > $edition->getDateCloture()) {
            $this->addFlash(
                "danger",
                "Les dépôts de candidatures pour l'édition " .
                    ($edition ? $edition->getAnnee() : date("Y")) .
                    " sont clôturés.",
            );
            return $this->redirectToRoute("app_home");
        }

        // 1. On crée une instance temporaire juste pour lier le formulaire au départ
        $candidatData = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidatData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nom = strtoupper($form->get("nom")->getData());
            $prenoms = strtoupper($form->get("prenoms")->getData());
            $email = strtolower($form->get("email")->getData());

            // 2. RECHERCHE DU CANDIDAT EXISTANT
            // On cherche si la personne physique existe déjà dans notre base globale
            $candidat = $this->candidatRepository->findOneBy([
                "nom" => $nom,
                "prenoms" => $prenoms,

                "email" => $email,
            ]);

            if ($candidat) {
                // Le candidat existe déjà ! On vérifie s'il n'a pas déjà postulé à CETTE édition précise
                $candidatureExist = $this->candidatureRepository->findOneBy([
                    "edition" => $edition,
                    "candidat" => $candidat,
                ]);

                if ($candidatureExist) {
                    $this->addFlash(
                        "danger",
                        "Vous avez déjà déposé une candidature pour l'édition " .
                            $edition->getAnnee() .
                            ".",
                    );
                    return $this->redirectToRoute("app_candidature_postuler");
                }

                //  Mettre à jour ses informations si elles ont changé (ex: téléphone, niveau d'étude)
                $candidat->setTelephone($form->get("telephone")->getData());
                $candidat->setNiveauEtude($form->get("niveauEtude")->getData());
            } else {
                // Le candidat n'existe pas du tout, on utilise notre nouvel objet et on génère son code de suivi unique
                $candidat = $candidatData;
                $candidat->setNom($nom)->setPrenoms($prenoms)->setEmail($email);
                $em->persist($candidat);
            }

            // 3. CRÉATION DE LA NOUVELLE CANDIDATURE (commune aux deux cas)
            $candidature = new Candidature();
            $candidature->setCandidat($candidat);
            $candidature->setDateSoumission(new \DateTime());

            $codeSuivi = "PEX-" . strtoupper(bin2hex(random_bytes(4)));
            while (
                $this->candidatureRepository->findOneBy([
                    "codeSuivi" => $codeSuivi,
                ])
            ) {
                $codeSuivi = "PEX-" . strtoupper(bin2hex(random_bytes(4)));
            }
            $candidature->setCodeSuivi($codeSuivi);
            $candidature
                ->setStatutDemande(StatutDemande::SOUMIS)
                ->setStatutTraitement(StatutTraitement::EN_ATTENTE)
                ->setEdition($edition);
            $em->persist($candidature);

            // 4. Traitement des fichiers téléversés
            $fileFields = [
                "contributionFile",
                "resumeFile",
                "cvFile",
                "identityFile",
                "diplomaFile",
            ];
            foreach ($fileFields as $field) {
                $uploadedFile = $form->get($field)->getData();
                if ($uploadedFile) {
                    $originalFilename = pathinfo(
                        $uploadedFile->getClientOriginalName(),
                        PATHINFO_FILENAME,
                    );
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename =
                        $safeFilename .
                        "-" .
                        uniqid() .
                        "." .
                        $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move(
                            $this->uploadsDirectory,
                            $newFilename,
                        );
$document = new Document();

$document->setNom($originalFilename);
$document->setType($field);

// Enregistrer uniquement le chemin relatif
$document->setChemin("uploads/documents/" . $newFilename);
$document->setCandidat($candidat);
$document->setCandidature($candidature);

$em->persist($document);
                       
                    } catch (FileException $e) {
                        $this->addFlash(
                            "danger",
                            "Une erreur est survenue lors de l'envoi des fichiers.",
                        );
                        return $this->redirectToRoute(
                            "app_candidature_postuler",
                        );
                    }
                }
            }

            $em->flush();
            $eventDispatcher->dispatch(
                new CandidatureAccepteEvent($candidature, $candidat),
                CandidatureAccepteEvent::NAME,
            );

            return $this->render("candidature/success.html.twig", [
                "codeSuivi" => $candidature->getCodeSuivi(),
                "candidat" => $candidat,
                "edition" => $edition,
            ]);
        }

        return $this->render("candidature/postuler.html.twig", [
            "form" => $form->createView(),
            "edition" => $edition,
        ]);
    }

    #[Route("/suivi", name: "app_suivi", methods: ["GET", "POST"])]
    public function suivi(Request $request): Response
    {
        $form = $this->createForm(SuiviType::class);
        $form->handleRequest($request);

        $candidature = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->get("codeSuivi")->getData();
            $candidature = $this->candidatureRepository->findOneByCodeSuivi(
                $code,
            );

            if (!$candidature) {
                $this->addFlash(
                    "error",
                    "Code de suivi introuvable ou incorrect.",
                );
            }
        }
        return $this->render("candidature/suivi.html.twig", [
            "form" => $form->createView(),
            "candidature" => $candidature,
        ]);
    }
}