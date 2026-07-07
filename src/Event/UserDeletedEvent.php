<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserDeletedEvent extends Event
{
   public const NAME = 'user.deleted';

   public function __construct(
      public readonly string $email,
      public readonly string $nom,
      public readonly array $roles,
      public readonly User $deletedBy,
   ) {}
}
