<?php

namespace App\Event;

use App\Entity\Candidat;
use App\Entity\Candidature;
use Symfony\Contracts\EventDispatcher\Event;

final class CandidatureAccepteEvent extends Event
{
    public const NAME = "candidature.accepte";

    public function __construct(
        public readonly Candidature $candidature,
        public readonly Candidat $candidat,
    ) {}
}
