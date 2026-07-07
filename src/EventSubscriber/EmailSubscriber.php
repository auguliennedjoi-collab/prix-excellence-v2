<?php

namespace App\EventSubscriber;

use App\Event\CandidatureAccepteEvent;
use App\Event\UserCreatedEvent;
use App\Event\ResetPasswordEvent;
use App\Service\EmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(private EmailService $emailService) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CandidatureAccepteEvent::NAME => "onCandidatureAccepte",
            UserCreatedEvent::NAME => "onUserCreated",
            ResetPasswordEvent::NAME => "onResetPassword",
        ];
    }

    public function onCandidatureAccepte(CandidatureAccepteEvent $event): void
    {
        $this->emailService->envoyerConfirmationDepotCandidature(
            $event->candidature,
            $event->candidat,
        );
    }

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $this->emailService->envoyerInfoCreationUser(
            $event->user,
            $event->plainPassword,
        );
        $this->emailService->envoyerConfirmationCreationUser(
            $event->user,
            $event->createdBy,
        );
    }

    public function onResetPassword(ResetPasswordEvent $event): void
    {
        $this->emailService->resetPassword($event->user, $event->resetToken);
    }
}
