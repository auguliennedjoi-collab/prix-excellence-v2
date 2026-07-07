<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserUpdatedEvent extends Event
{
   public const NAME = 'user.updated';

   public function __construct(
      public readonly User $user,
      public readonly User $updatedBy,
      public readonly array $champsModifies,
   ) {}
}
