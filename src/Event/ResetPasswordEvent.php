<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

final class ResetPasswordEvent extends Event
{
    public const NAME = 'password.reset';

    public function __construct(
        public readonly User $user,
        public readonly ResetPasswordToken $resetToken
    ) {}
}
