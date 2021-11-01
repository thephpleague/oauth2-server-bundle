<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\EventListener;

use League\Bundle\OAuth2ServerBundle\Security\Exception\InsufficientScopesException;
use League\Bundle\OAuth2ServerBundle\Security\Passport\Badge\ScopeBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Checks that requested scopes are matching with token scopes.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CheckScopeListener implements EventSubscriberInterface
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        /**
         * @var Passport $passport
         * @psalm-suppress DeprecatedClass
         */
        $passport = $event->getPassport();
        if (!$passport->hasBadge(ScopeBadge::class)) {
            return;
        }

        /** @var ScopeBadge $badge */
        $badge = $passport->getBadge(ScopeBadge::class);
        if ($badge->isResolved()) {
            return;
        }

        /** @var Request $request */
        $request = $this->requestStack->{method_exists($this->requestStack, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest'}();

        /** @var list<string> $requestedScopes */
        $requestedScopes = $request->attributes->get('oauth2_scopes', []);

        if ([] !== $requestedScopes && [] !== array_diff($requestedScopes, $badge->getScopes())) {
            throw InsufficientScopesException::create();
        }

        $badge->markResolved();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['checkPassport', 256],
        ];
    }
}
