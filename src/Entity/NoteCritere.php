<?php

namespace App\Entity;

use App\Repository\NoteCritereRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteCritereRepository::class)]
#[
    ORM\UniqueConstraint(
        name: "uniq_evaluation_critere",
        columns: ["evaluation_id", "critere_id"],
    ),
]
class NoteCritere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Evaluation::class, inversedBy: "noteCriteres")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\ManyToOne(targetEntity: Critere::class, inversedBy: "noteCriteres")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Critere $critere = null;

    #[ORM\Column]
    private ?float $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;
        return $this;
    }

    public function getCritere(): ?Critere
    {
        return $this->critere;
    }

    public function setCritere(?Critere $critere): static
    {
        $this->critere = $critere;
        return $this;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(float $note): static
    {
        $this->note = $note;
        return $this;
    }
}