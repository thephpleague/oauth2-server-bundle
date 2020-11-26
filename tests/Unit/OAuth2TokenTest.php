<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class OAuth2TokenTest extends TestCase
{
    public function testTokenSerialization(): void
    {
        $scopes = [FixtureFactory::FIXTURE_SCOPE_FIRST];
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('oauth_scopes', [])
            ->willReturn($scopes);

        $user = new User();
        $rolePrefix = 'ROLE_OAUTH2_';
        $providerKey = 'main';
        $token = new OAuth2Token($serverRequest, $user, $rolePrefix, $providerKey);

        /** @var OAuth2Token $unserializedToken */
        $unserializedToken = unserialize(serialize($token));

        $this->assertSame($providerKey, $unserializedToken->getProviderKey());

        $expectedRole = $rolePrefix . strtoupper($scopes[0]);
        $this->assertSame([$expectedRole], $token->getRoleNames());

        $this->assertSame($user->getUsername(), $unserializedToken->getUser()->getUsername());
        $this->assertFalse($unserializedToken->isAuthenticated());
    }
}
