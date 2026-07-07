<?php

namespace App\EventSubscriber;

use App\Event\UserCreatedEvent;
use App\Event\UserDeletedEvent;
use App\Event\UserUpdatedEvent;
use App\Service\AuditService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuditSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuditService $audit) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Sécurité
            LoginSuccessEvent::class => "onLogin",
            LogoutEvent::class => "onLogout",

            // CRUD utilisateurs
            UserCreatedEvent::NAME => "onUserCreated",
            UserUpdatedEvent::NAME => "onUserUpdated",
            UserDeletedEvent::NAME => "onUserDeleted",
        ];
    }

    // ── Sécurité ──────────────────────────────────────────────────────────────

    public function onLogin(LoginSuccessEvent $event): void
    {
        $this->audit->log(
            action: "LOGIN",
            user: $event->getUser(),
            contexte: ["route" => $event->getRequest()->getPathInfo()],
        );
    }

    public function onLogout(LogoutEvent $event): void
    {
        $this->audit->log(
            action: "LOGOUT",
            user: $event->getToken()?->getUser(),
            contexte: [],
        );
    }

    // ── CRUD utilisateurs ─────────────────────────────────────────────────────

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $this->audit->log(
            action: "USER_CREATED",
            user: $event->createdBy,
            contexte: [
                "user_id" => $event->user->getId(),
                "email" => $event->user->getEmail(),
                "roles" => $event->user->getRoles(),
            ],
        );
    }

    public function onUserUpdated(UserUpdatedEvent $event): void
    {
        $this->audit->log(
            action: "USER_UPDATED",
            user: $event->updatedBy,
            contexte: [
                "user_id" => $event->user->getId(),
                "email" => $event->user->getEmail(),
                "champs_modifies" => $event->champsModifies,
            ],
        );
    }

    public function onUserDeleted(UserDeletedEvent $event): void
    {
        $this->audit->log(
            action: "USER_DELETED",
            user: $event->deletedBy,
            contexte: [
                "email" => $event->email,
                "nom" => $event->nom,
                "roles" => $event->roles,
            ],
        );
    }
}
