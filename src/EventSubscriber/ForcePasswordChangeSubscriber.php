<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForcePasswordChangeSubscriber implements EventSubscriberInterface
{
    private const ROUTES_AUTORISEES = [
        'app_change_password_first_login',
        'app_logout',
    ];

    public function __construct(
        private readonly Security              $security,
        private readonly UrlGeneratorInterface  $urlGenerator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isMustChangePassword()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (in_array($route, self::ROUTES_AUTORISEES, true)) {
            return;
        }

        // Ignore les assets, profiler, etc.
        if (str_starts_with($route ?? '', '_')) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_change_password_first_login')
        ));
    }
}
