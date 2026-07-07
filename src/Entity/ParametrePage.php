<?php

namespace App\Entity;

use App\Repository\ParametrePageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametrePageRepository::class)]
class ParametrePage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateDebutEtude = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateFinEtude = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateDebutPreselection = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateFinPreselection = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateDebutAudition = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateFinAudition = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateDebutProclamation = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateFinProclamation = null;

    #[ORM\Column(type: "text")]
    private ?string $quiPeutParticiper = "";

    #[ORM\Column(type: "json")]
    private ?array $dossierRequis = [];

    #[ORM\Column(type: "json")]
    private ?array $recompenses = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $footerTexte = "© 2026 Cour Suprême du Bénin — Tous droits réservés";

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebutEtude(): ?\DateTime
    {
        return $this->dateDebutEtude;
    }

    public function setDateDebutEtude(\DateTime $dateDebutEtude): static
    {
        $this->dateDebutEtude = $dateDebutEtude;

        return $this;
    }

    public function getDateFinEtude(): ?\DateTime
    {
        return $this->dateFinEtude;
    }

    public function setDateFinEtude(\DateTime $dateFinEtude): static
    {
        $this->dateFinEtude = $dateFinEtude;

        return $this;
    }

    public function getDateDebutPreselection(): ?\DateTime
    {
        return $this->dateDebutPreselection;
    }

    public function setDateDebutPreselection(
        \DateTime $dateDebutPreselection,
    ): static {
        $this->dateDebutPreselection = $dateDebutPreselection;

        return $this;
    }

    public function getDateFinPreselection(): ?\DateTime
    {
        return $this->dateFinPreselection;
    }

    public function setDateFinPreselection(
        \DateTime $dateFinPreselection,
    ): static {
        $this->dateFinPreselection = $dateFinPreselection;

        return $this;
    }

    public function getDateDebutAudition(): ?\DateTime
    {
        return $this->dateDebutAudition;
    }

    public function setDateDebutAudition(\DateTime $dateDebutAudition): static
    {
        $this->dateDebutAudition = $dateDebutAudition;

        return $this;
    }

    public function getDateFinAudition(): ?\DateTime
    {
        return $this->dateFinAudition;
    }

    public function setDateFinAudition(\DateTime $dateFinAudition): static
    {
        $this->dateFinAudition = $dateFinAudition;

        return $this;
    }

    public function getDateDebutProclamation(): ?\DateTime
    {
        return $this->dateDebutProclamation;
    }

    public function setDateDebutProclamation(
        \DateTime $dateDebutProclamation,
    ): static {
        $this->dateDebutProclamation = $dateDebutProclamation;

        return $this;
    }

    public function getDateFinProclamation(): ?\DateTime
    {
        return $this->dateFinProclamation;
    }

    public function setDateFinProclamation(
        \DateTime $dateFinProclamation,
    ): static {
        $this->dateFinProclamation = $dateFinProclamation;

        return $this;
    }

    public function getQuiPeutParticiper(): ?string
    {
        return $this->quiPeutParticiper;
    }

    public function setQuiPeutParticiper(string $quiPeutParticiper): static
    {
        $this->quiPeutParticiper = $quiPeutParticiper;

        return $this;
    }

    public function getDossierRequis(): array
    {
        return $this->dossierRequis;
    }

    public function setDossierRequis(array $dossierRequis): static
    {
        $this->dossierRequis = $dossierRequis;

        return $this;
    }

    public function getRecompenses(): array
    {
        return $this->recompenses;
    }

    public function setRecompenses(array $recompenses): static
    {
        $this->recompenses = $recompenses;

        return $this;
    }

    public function getFooterTexte(): ?string
    {
        return $this->footerTexte;
    }

    public function setFooterTexte(?string $footerTexte): static
    {
        $this->footerTexte = $footerTexte;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
