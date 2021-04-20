<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\EventListener\CheckScopesListener;
use League\Bundle\OAuth2ServerBundle\Security\Exception\InsufficientScopesException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class CheckScopesListenerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(AuthenticatorInterface::class)) {
            $this->markTestSkipped('Authenticator security system is not available.');
        }

        parent::setUp();
    }

    public function testNoScopeRequested(): void
    {
        $event = new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(PassportInterface::class),
            new OAuth2Token(null, 'accessTokenId', [], 'rolePrefix'),
            new Request(),
            null,
            'firewallName'
        );

        (new CheckScopesListener())->onLoginSuccess($event);

        $this->addToAssertionCount(1);
    }

    public function testIdenticalRequestedAndTokenScopes(): void
    {
        $event = new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(PassportInterface::class),
            new OAuth2Token(null, 'accessTokenId', ['scope_one'], 'rolePrefix'),
            new Request([], [], ['oauth2_scopes' => ['scope_one']]),
            null,
            'firewallName'
        );

        (new CheckScopesListener())->onLoginSuccess($event);

        $this->addToAssertionCount(1);
    }

    public function testDifferentRequestedAndTokenScopes(): void
    {
        $event = new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(PassportInterface::class),
            new OAuth2Token(null, 'accessTokenId', ['scope_one'], 'rolePrefix'),
            new Request([], [], ['oauth2_scopes' => ['scope_two']]),
            null,
            'firewallName'
        );

        $this->expectException(InsufficientScopesException::class);

        (new CheckScopesListener())->onLoginSuccess($event);
    }
}
