<?php

namespace App\DataFixtures;

use App\Entity\Edition;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    // On injecte le service de hachage de mot de passe via le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création de l'édition 2026
        $edition2026 = new Edition();
        $edition2026->setAnnee(2026);
        $edition2026->setEdition(5); // 5ème édition
        $edition2026->setTheme(
            "Le caractère suspensif du pourvoi en cassation en matière foncière : " .
                "entre sécurisation du droit de propriété foncière et efficacité de la justice",
        );
        $edition2026->setDateLancement(new \DateTime("2026-05-26 00:00:00"));
        $edition2026->setDateCloture(new \DateTime("2026-09-25 17:00:00"));

        $manager->persist($edition2026);

        // 2. Tableau des utilisateurs à créer
        $usersData = [
            [
                "nom" => "Admin",
                "prenom" => "super",
                "email" => "andilangesmartialaminou@gmail.com",
                "roles" => ["ROLE_ADMIN"],
                "password" => "123456789",
            ],
            [
                "nom" => "Jury",
                "prenom" => "Member",
                "email" => "jury@cour.bj",
                "roles" => ["ROLE_JURY"],
                "password" => "123456789",
            ],
            [
                "nom" => "Responsable",
                "prenom" => "Chef",
                "email" => "responsable@cour.bj",
                "roles" => ["ROLE_VERIFICATEUR"],
                "password" => "123456789",
            ],
        ];

        // 3. Boucle pour hydrater et persister les utilisateurs
        foreach ($usersData as $data) {
            $user = new User();
            $user->setNom($data["nom"]);
            $user->setPrenoms($data["prenom"]); // Adapté selon votre propriété (setPrenoms)
            $user->setEmail($data["email"]);
            $user->setRoles($data["roles"]);

            // Hachage sécurisé du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data["password"],
            );
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        // On envoie tout en base de données en une seule fois
        $manager->flush();
    }
}
