<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator;
use League\Bundle\OAuth2ServerBundle\Security\Exception\OAuth2AuthenticationFailedException;
use League\Bundle\OAuth2ServerBundle\Security\Passport\Badge\ScopeBadge;
use League\Bundle\OAuth2ServerBundle\Security\User\NullUser;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OAuth2AuthenticatorTest extends TestCase
{
    public function testAuthenticateThrowIfCannotValidateAuthenticatedRequest(): void
    {
        $httpMessageFactory = $this->createMock(HttpMessageFactoryInterface::class);
        $httpMessageFactory
            ->method('createRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class))
        ;

        $resourceServer = $this->createMock(ResourceServer::class);
        $resourceServer
            ->method('validateAuthenticatedRequest')
            ->willThrowException(new OAuthServerException('foo', 0, 'bar'))
        ;

        $authenticator = new OAuth2Authenticator(
            $httpMessageFactory,
            $resourceServer,
            $this->createMock(UserProviderInterface::class),
            'PREFIX_'
        );

        $this->expectException(OAuth2AuthenticationFailedException::class);
        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateCreatePassport(): void
    {
        $serverRequest = (new ServerRequest('GET', '/foo'))
            ->withAttribute('oauth_user_id', 'userIdentifier')
            ->withAttribute('oauth_access_token_id', 'accessTokenId')
            ->withAttribute('oauth_scopes', ['scope_one', 'scope_two'])
        ;

        $httpMessageFactory = $this->createMock(HttpMessageFactoryInterface::class);
        $httpMessageFactory
            ->method('createRequest')
            ->willReturn($serverRequest)
        ;

        $resourceServer = $this->createMock(ResourceServer::class);
        $resourceServer
            ->method('validateAuthenticatedRequest')
            ->willReturn($serverRequest)
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('userIdentifier')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $authenticator = new OAuth2Authenticator(
            $httpMessageFactory,
            $resourceServer,
            $userProvider,
            'PREFIX_'
        );

        /** @var Passport $passport */
        $passport = $authenticator->authenticate(new Request());

        $this->assertSame('accessTokenId', $passport->getAttribute('accessTokenId'));
        $this->assertSame(['scope_one', 'scope_two'], $passport->getBadge(ScopeBadge::class)->getScopes());

        $passport->getUser();
    }

    public function testAuthenticateCreatePassportWithNullUser(): void
    {
        $serverRequest = (new ServerRequest('GET', '/foo'))
            ->withAttribute('oauth_access_token_id', 'accessTokenId')
        ;

        $httpMessageFactory = $this->createMock(HttpMessageFactoryInterface::class);
        $httpMessageFactory
            ->method('createRequest')
            ->willReturn($serverRequest)
        ;

        $resourceServer = $this->createMock(ResourceServer::class);
        $resourceServer
            ->method('validateAuthenticatedRequest')
            ->willReturn($serverRequest)
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects($this->never())
            ->method('loadUserByUsername')
        ;

        $authenticator = new OAuth2Authenticator(
            $httpMessageFactory,
            $resourceServer,
            $userProvider,
            'PREFIX_'
        );

        /** @var Passport $passport */
        $passport = $authenticator->authenticate(new Request());

        $this->assertInstanceOf(NullUser::class, $passport->getUser());
    }

    public function testCreateAuthenticatedToken(): void
    {
        if (!class_exists(UserBadge::class)) {
            $userBadge = new NullUser();
        } else {
            $userBadge = new UserBadge('userIdentifier', static function (): UserInterface {
                return new NullUser();
            });
        }

        $passport = new SelfValidatingPassport($userBadge, [
            new ScopeBadge(['scope_one', 'scope_two']),
        ]);
        $passport->setAttribute('accessTokenId', 'accessTokenId');

        $authenticator = new OAuth2Authenticator(
            $this->createMock(HttpMessageFactoryInterface::class),
            $this->createMock(ResourceServer::class),
            $this->createMock(UserProviderInterface::class),
            'PREFIX_'
        );

        $token = $authenticator->createAuthenticatedToken($passport, 'firewallName');

        $this->assertSame(['scope_one', 'scope_two'], $token->getScopes());
        $this->assertSame('accessTokenId', $token->getCredentials());
        $this->assertInstanceOf(NullUser::class, $token->getUser());
        $this->assertTrue($token->isAuthenticated());
    }
}
