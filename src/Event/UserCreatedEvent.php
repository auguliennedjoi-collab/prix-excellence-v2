<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserCreatedEvent extends Event
{
    public const NAME = 'user.created';

    public function __construct(
        public readonly User $user,
        public readonly User $createdBy,
        public readonly string $plainPassword,
    ) {}
}
