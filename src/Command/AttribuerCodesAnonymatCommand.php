<?php

namespace App\Command;

use App\Entity\Candidature;
use App\Enum\StatutDemande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: "app:attribuer-codes-anonymat",
    description: "Attribue un code d'anonymat aux candidatures validées qui n'en ont pas encore.",
)]
class AttribuerCodesAnonymatCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(Candidature::class);

        // On traite édition par édition pour numéroter séparément
        $editions = $this->em->getRepository(\App\Entity\Edition::class)->findAll();

       
            // Candidatures validées sans code, triées par date de soumission
          foreach ($editions as $edition) {
    $annee = $edition->getAnnee();

    $codesExistants = $repo->createQueryBuilder("c")
        ->select("c.codeAnonymat")
        ->andWhere("c.edition = :edition")
        ->andWhere("c.codeAnonymat IS NOT NULL")
        ->setParameter("edition", $edition)
        ->getQuery()
        ->getSingleColumnResult();

    $dernierNumero = 0;
    foreach ($codesExistants as $code) {
        if (preg_match('/-(\d+)$/', $code, $matches)) {
            $dernierNumero = max($dernierNumero, (int) $matches[1]);
        }
    }

    $candidaturesSansCode = $repo->createQueryBuilder("c")
        ->andWhere("c.edition = :edition")
        ->andWhere("c.codeAnonymat IS NULL")
        ->andWhere("c.statutDemande = :statut")
        ->setParameter("edition", $edition)
        ->setParameter("statut", StatutDemande::VALIDE)
        ->orderBy("c.dateSoumission", "ASC")
        ->getQuery()
        ->getResult();

    foreach ($candidaturesSansCode as $candidature) {
        $dernierNumero++;
        $code = sprintf("CAN-%s-%03d", $annee, $dernierNumero);
        $candidature->setCodeAnonymat($code);
        $io->writeln(sprintf(
            "%s attribué à la candidature %s",
            $code,
            $candidature->getCodeSuivi(),
        ));
    }
} 

        $this->em->flush();
        $io->success("Codes d'anonymat attribués avec succès.");

        return Command::SUCCESS;
    }
}