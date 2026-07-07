<?php

namespace App\Enum;

enum StatutDemande: string
{
    case BROUILLON = "brouillon";
    case SOUMIS = "soumis";
    case INCOMPLET = "incomplet";
    case VALIDE = "valide";
    case REJETE = "rejete";

    /**
     * Retourne le label lisible en français
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BROUILLON => "Brouillon",
            self::SOUMIS => "Soumis (En attente de vérification)",
            self::INCOMPLET => "Dossier incomplet",
            self::VALIDE => "Validé administrativement",
            self::REJETE => "Rejeté / Non recevable",
        };
    }

    /**
     *  Retourne une couleur Bootstrap associée au statut
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::BROUILLON => "secondary",
            self::SOUMIS => "warning text-dark",
            self::INCOMPLET => "info text-dark",
            self::VALIDE => "success",
            self::REJETE => "danger",
        };
    }
}
