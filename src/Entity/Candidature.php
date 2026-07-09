<?php

namespace App\Entity;

use App\Enum\StatutDemande;
use App\Enum\StatutTraitement;
use App\Repository\CandidatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Tes\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV7 as Uuid;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id = null;

    #[ORM\Column]
    private ?\DateTime $dateSoumission = null;

    #[ORM\Column(length: 100)]
    private ?StatutDemande $statutDemande = null;

    #[ORM\Column(length: 100)]
    private ?StatutTraitement $statutTraitement = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $codeSuivi = null;

    #[ORM\Column(nullable: true)]
    private ?float $note = null;

    /**
     * Taux de plagiat évalué par l'ADMIN (et non plus par le jury),
     * uniquement pour les candidatures présélectionnées (top 7).
     */
    #[ORM\Column(nullable: true)]
    private ?float $tauxPlagiat = null;

    #[ORM\ManyToOne(inversedBy: "candidature")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidat $candidat = null;

    /**
     * @var Collection<int, Document>
     */
    #[
        ORM\OneToMany(
            targetEntity: Document::class,
            mappedBy: "candidature",
            orphanRemoval: true,
        ),
    ]
    private Collection $document;

    #[ORM\ManyToOne(inversedBy: "candidatures")]
    private ?Edition $edition = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: "candidature")]
    private Collection $evaluations;

    public function __construct()
    {
        $this->document = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDateSoumission(): ?\DateTime
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTime $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getStatutDemande(): ?StatutDemande
    {
        return $this->statutDemande;
    }

    public function setStatutDemande(StatutDemande $statutDemande): static
    {
        $this->statutDemande = $statutDemande;

        return $this;
    }

    public function getStatutTraitement(): ?StatutTraitement
    {
        return $this->statutTraitement;
    }

    public function setStatutTraitement(
        ?StatutTraitement $statutTraitement,
    ): static {
        $this->statutTraitement = $statutTraitement;

        return $this;
    }

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(?Candidat $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocument(): Collection
    {
        return $this->document;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->document->contains($document)) {
            $this->document->add($document);
            $document->setCandidature($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->document->removeElement($document)) {
            if ($document->getCandidature() === $this) {
                $document->setCandidature(null);
            }
        }

        return $this;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    public function getCodeSuivi(): ?string
    {
        return $this->codeSuivi;
    }

    public function setCodeSuivi(?string $codeSuivi): static
    {
        $this->codeSuivi = $codeSuivi;

        return $this;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): static
    {
        $this->note = $note;

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

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setCandidature($this);
        }

        return $this;
    }
}