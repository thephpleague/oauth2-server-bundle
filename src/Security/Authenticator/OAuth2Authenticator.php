<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authenticator;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Exception\OAuth2AuthenticationException;
use League\Bundle\OAuth2ServerBundle\Security\Exception\OAuth2AuthenticationFailedException;
use League\Bundle\OAuth2ServerBundle\Security\Passport\Badge\ScopeBadge;
use League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUser;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class OAuth2Authenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    private HttpMessageFactoryInterface $httpMessageFactory;
    private ResourceServer $resourceServer;
    /** @var UserProviderInterface<UserInterface> */
    private UserProviderInterface $userProvider;
    private string $rolePrefix;

    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        ResourceServer $resourceServer,
        UserProviderInterface $userProvider,
        string $rolePrefix,
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->resourceServer = $resourceServer;
        $this->userProvider = $userProvider;
        $this->rolePrefix = $rolePrefix;
    }

    public function supports(Request $request): bool
    {
        return str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response($authException?->getMessage() ?? 'Authentication required', 401, ['WWW-Authenticate' => 'Bearer']);
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $psr7Request = $this->resourceServer->validateAuthenticatedRequest($this->httpMessageFactory->createRequest($request));
        } catch (OAuthServerException $e) {
            throw OAuth2AuthenticationFailedException::create('The resource server rejected the request.', $e);
        }

        /** @var string $userIdentifier */
        $userIdentifier = $psr7Request->getAttribute('oauth_user_id', '');
        if ('' === $userIdentifier) {
            /**
             * BC layer for Symfony < 8.0
             */
            if (is_a(ChainUserProvider::class, AttributesBasedUserProviderInterface::class, true)) {
                throw OAuth2AuthenticationFailedException::create('The access token has either an empty or missing "oauth_user_id" attribute.');
            }
        }

        /** @var string $accessTokenId */
        $accessTokenId = $psr7Request->getAttribute('oauth_access_token_id');

        /** @var list<string> $scopes */
        $scopes = $psr7Request->getAttribute('oauth_scopes', []);

        /** @var non-empty-string $oauthClientId */
        $oauthClientId = $psr7Request->getAttribute('oauth_client_id', '');

        $userLoader = function (string $userIdentifier) use ($oauthClientId): UserInterface {
            if (
                $oauthClientId === $userIdentifier
                || ('' === $userIdentifier && is_a(ChainUserProvider::class, AttributesBasedUserProviderInterface::class, true)) // BC layer for Symfony < 8.0
            ) {
                return new ClientCredentialsUser($oauthClientId);
            }

            return $this->userProvider->loadUserByIdentifier($userIdentifier);
        };

        $passport = new SelfValidatingPassport(new UserBadge($userIdentifier, $userLoader), [
            new ScopeBadge($scopes),
        ]);

        $passport->setAttribute('accessTokenId', $accessTokenId);
        $passport->setAttribute('oauthClientId', $oauthClientId);

        return $passport;
    }

    /**
     * @return OAuth2Token
     */
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        /** @var string $accessTokenId */
        $accessTokenId = $passport->getAttribute('accessTokenId');

        /** @var ScopeBadge $scopeBadge */
        $scopeBadge = $passport->getBadge(ScopeBadge::class);

        /** @var string $oauthClientId */
        $oauthClientId = $passport->getAttribute('oauthClientId', '');

        return new OAuth2Token($passport->getUser(), $accessTokenId, $oauthClientId, $scopeBadge->getScopes(), $this->rolePrefix);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof OAuth2AuthenticationException) {
            return new Response($exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
        }

        throw $exception;
    }
}
