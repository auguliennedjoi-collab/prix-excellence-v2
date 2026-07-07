<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV7 as Uuid;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[
    ORM\UniqueConstraint(
        name: "uniq_jury_candidature",
        columns: ["jury_id", "candidature_id"],
    ),
]
class Evaluation
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jury = null;

    #[ORM\ManyToOne(targetEntity: Candidature::class, inversedBy: "evaluations")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidature $candidature = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTime $dateEvaluation = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteEcrite = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteOrale = null;

    #[ORM\Column(nullable: true)]
    private ?float $tauxPlagiat = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteFinale = null;

    /**
     * @var Collection<int, NoteCritere>
     */
    #[
        ORM\OneToMany(
            targetEntity: NoteCritere::class,
            mappedBy: "evaluation",
            cascade: ["persist"],
            orphanRemoval: true,
        ),
    ]
    private Collection $noteCriteres;

    public function __construct()
    {
        $this->noteCriteres = new ArrayCollection();
        $this->dateEvaluation = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getJury(): ?User
    {
        return $this->jury;
    }

    public function setJury(?User $jury): static
    {
        $this->jury = $jury;
        return $this;
    }

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): static
    {
        $this->candidature = $candidature;
        return $this;
    }

    public function getDateEvaluation(): ?\DateTime
    {
        return $this->dateEvaluation;
    }

    public function setDateEvaluation(\DateTime $dateEvaluation): static
    {
        $this->dateEvaluation = $dateEvaluation;
        return $this;
    }

    public function getNoteEcrite(): ?float
    {
        return $this->noteEcrite;
    }

    public function setNoteEcrite(?float $noteEcrite): static
    {
        $this->noteEcrite = $noteEcrite;
        return $this;
    }

    public function getNoteOrale(): ?float
    {
        return $this->noteOrale;
    }

    public function setNoteOrale(?float $noteOrale): static
    {
        $this->noteOrale = $noteOrale;
        return $this;
    }

    public function getTauxPlagiat(): ?float
    {
        return $this->tauxPlagiat;
    }

    public function setTauxPlagiat(?float $tauxPlagiat): static
    {
        $this->tauxPlagiat = $tauxPlagiat;
        return $this;
    }

    public function getNoteFinale(): ?float
    {
        return $this->noteFinale;
    }

    /**
     * @return Collection<int, NoteCritere>
     */
    public function getNoteCriteres(): Collection
    {
        return $this->noteCriteres;
    }

    public function addNoteCritere(NoteCritere $noteCritere): static
    {
        if (!$this->noteCriteres->contains($noteCritere)) {
            $this->noteCriteres->add($noteCritere);
            $noteCritere->setEvaluation($this);
        }
        return $this;
    }

    public function removeNoteCritere(NoteCritere $noteCritere): static
    {
        $this->noteCriteres->removeElement($noteCritere);
        return $this;
    }

    public function getNoteForCritere(Critere $critere): ?float
    {
        foreach ($this->noteCriteres as $noteCritere) {
            if ($noteCritere->getCritere() === $critere) {
                return $noteCritere->getNote();
            }
        }
        return null;
    }

    public function calculerNoteFinale(): float
    {
        $totalEcrit = 0.0;
        foreach ($this->noteCriteres as $noteCritere) {
            $totalEcrit += $noteCritere->getNote();
        }
        $this->noteEcrite = round($totalEcrit, 2);

        $noteOrale = $this->noteOrale ?? 0.0;

        $final = 0.75 * $this->noteEcrite + 0.25 * $noteOrale;
        $this->noteFinale = round($final, 2);

        return $this->noteFinale;
    }
}