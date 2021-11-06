<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\EventListener\CheckScopeListener;
use League\Bundle\OAuth2ServerBundle\Security\Exception\InsufficientScopesException;
use League\Bundle\OAuth2ServerBundle\Security\Passport\Badge\ScopeBadge;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final class CheckScopeListenerTest extends TestCase
{
    public function testNoScopeRequested(): void
    {
        $event = new CheckPassportEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(Passport::class)
        );

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        (new CheckScopeListener($requestStack))->checkPassport($event);

        $this->addToAssertionCount(1);
    }

    public function testIdenticalRequestedAndTokenScopes(): void
    {
        $passport = new SelfValidatingPassport(new UserBadge('foo'), [
            new ScopeBadge(['scope_one']),
        ]);

        $event = new CheckPassportEvent(
            $this->createMock(AuthenticatorInterface::class),
            $passport
        );

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['oauth2_scopes' => ['scope_one']]));

        (new CheckScopeListener($requestStack))->checkPassport($event);

        $this->addToAssertionCount(1);
    }

    public function testDifferentRequestedAndTokenScopes(): void
    {
        $passport = new SelfValidatingPassport(new UserBadge('foo'), [
            new ScopeBadge(['scope_one']),
        ]);

        $event = new CheckPassportEvent(
            $this->createMock(AuthenticatorInterface::class),
            $passport
        );

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['oauth2_scopes' => ['scope_two']]));

        $this->expectException(InsufficientScopesException::class);

        (new CheckScopeListener($requestStack))->checkPassport($event);
    }
}
