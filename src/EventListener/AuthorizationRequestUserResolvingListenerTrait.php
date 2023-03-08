<?php

namespace League\Bundle\OAuth2ServerBundle\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use Symfony\Component\Security\Core\User\UserInterface;

trait AuthorizationRequestUserResolvingListenerTrait
{
    public function onAuthorizationRequest(AuthorizationRequestResolveEvent $event): void
    {
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $event->setUser($user);
        }
    }
}
