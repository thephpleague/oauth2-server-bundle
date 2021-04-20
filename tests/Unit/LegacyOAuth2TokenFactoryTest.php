<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\LegacyOAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\LegacyOAuth2TokenFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class LegacyOAuth2TokenFactoryTest extends TestCase
{
    public function testCreatingToken(): void
    {
        $rolePrefix = 'ROLE_OAUTH2_';
        $factory = new LegacyOAuth2TokenFactory($rolePrefix);

        $scopes = [FixtureFactory::FIXTURE_SCOPE_FIRST];
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('oauth_scopes', [])
            ->willReturn($scopes);

        $user = new User();
        $providerKey = 'main';

        $token = $factory->createOAuth2Token($serverRequest, $user, $providerKey);

        $this->assertInstanceOf(LegacyOAuth2Token::class, $token);

        $roles = $token->getRoleNames();
        $this->assertCount(1, $roles);
        $this->assertSame($rolePrefix . strtoupper($scopes[0]), $roles[0]);

        $this->assertFalse($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
        $this->assertSame($providerKey, $token->getProviderKey());
    }
}
