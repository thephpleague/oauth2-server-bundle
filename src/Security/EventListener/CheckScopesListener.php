<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\EventListener;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Exception\InsufficientScopesException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Checks that requested scopes are matching with token scopes.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CheckScopesListener implements EventSubscriberInterface
{
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var list<string> $requestedScopes */
        $requestedScopes = $event->getRequest()->attributes->get('oauth2_scopes', []);
        if ([] === $requestedScopes) {
            return;
        }

        /** @var OAuth2Token $token */
        $token = $event->getAuthenticatedToken();
        if ([] === array_diff($requestedScopes, $token->getScopes())) {
            return;
        }

        throw InsufficientScopesException::create($token);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 256],
        ];
    }
}
