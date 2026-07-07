<?php

namespace App\Enum;

enum StatutTraitement: string
{
    case EN_ATTENTE = "en_attente";
    case EN_COURS_ETUDE = "en_cours_etude";
    case PRESELECTIONNE = "preselectionne";
    case LAUREAT = "laureat";
    case NON_RETENU = "non_retenu";
    case ELIMINE_PLAGIAT = "elimine_plagiat";

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente d\'examen',
            self::EN_COURS_ETUDE => 'En cours d\'étude par le jury',
            self::PRESELECTIONNE => "Présélectionné pour les auditions",
            self::LAUREAT => 'Lauréat du Prix d\'Excellence 🏆',
            self::NON_RETENU => "Non retenu",
            self::ELIMINE_PLAGIAT => "Éliminé pour plagiat",
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::EN_ATTENTE => "secondary",
            self::EN_COURS_ETUDE => "primary",
            self::PRESELECTIONNE => "info text-dark",
            self::LAUREAT => "success fw-bold",
            self::NON_RETENU => "danger",
            self::ELIMINE_PLAGIAT => "dark fw-bold",
        };
    }
}