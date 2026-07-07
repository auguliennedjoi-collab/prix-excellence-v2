<?php

namespace App\Command;

use App\Entity\Critere;
use App\Repository\CritereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "app:seed-criteres",
    description: "Insère les 7 critères d'évaluation du Prix d'Excellence",
)]
class SeedCriteresCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CritereRepository $critereRepository,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $criteres = [
            ["Compréhension du sujet", 3.0, 1],
            ["Cohérence du plan", 2.0, 2],
            ["Effort de recherche", 4.0, 3],
            ["Qualité de rédaction", 2.0, 4],
            [
                "Qualité formelle (pages, propreté, notes de bas de page)",
                2.0,
                5,
            ],
            ["Qualité de l'argumentation", 3.0, 6],
            ["Originalité et pertinence des propositions", 4.0, 7],
        ];

        foreach ($criteres as [$nom, $noteMax, $ordre]) {
            $existant = $this->critereRepository->findOneBy(["nom" => $nom]);
            if ($existant) {
                continue;
            }

            $critere = new Critere();
            $critere->setNom($nom);
            $critere->setNoteMax($noteMax);
            $critere->setOrdre($ordre);
            $this->em->persist($critere);

            $output->writeln("✅ Ajouté : $nom ($noteMax pts)");
        }

        $this->em->flush();
        $output->writeln("Terminé.");

        return Command::SUCCESS;
    }
}