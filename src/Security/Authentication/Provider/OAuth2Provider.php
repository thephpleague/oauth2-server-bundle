<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Provider;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2TokenFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class OAuth2Provider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var ResourceServer
     */
    private $resourceServer;

    /**
     * @var OAuth2TokenFactory
     */
    private $oauth2TokenFactory;

    /**
     * @var string
     */
    private $providerKey;
    private $requestStack;
    private $httpMessageFactory;

    public function __construct(
        UserProviderInterface $userProvider,
        ResourceServer $resourceServer,
        OAuth2TokenFactory $oauth2TokenFactory,
        RequestStack $requestStack,
        HttpMessageFactoryInterface $httpMessageFactory,
        string $providerKey
    ) {
        $this->userProvider = $userProvider;
        $this->resourceServer = $resourceServer;
        $this->oauth2TokenFactory = $oauth2TokenFactory;
        $this->providerKey = $providerKey;
        $this->requestStack = $requestStack;
        $this->httpMessageFactory = $httpMessageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token, Request $request = null)
    {
        if (!$this->supports($token)) {
            throw new \RuntimeException(sprintf('This authentication provider can only handle tokes of type \'%s\'.', OAuth2Token::class));
        }

        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $psrRequest = $this->httpMessageFactory->createRequest($request);

        try {
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException('The resource server rejected the request.', 0, $e);
        }

        /** @var list<string> $scopes */
        $scopes = (string) $psrRequest->getAttribute('oauth_scopes');
        $userId = (string) $psrRequest->getAttribute('oauth_user_id');
        $clientId = (string) $psrRequest->getAttribute('oauth_client_id');
        $accessTokenId = (string) $psrRequest->getAttribute('oauth_access_token_id');

        $user = $this->getAuthenticatedUser($userId);

        $token = $this->oauth2TokenFactory->createOAuth2Token($user, $scopes, $clientId, $accessTokenId, $this->providerKey);
        $token->setAuthenticated(true);

        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-assert-if-true OAuth2Token $token
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuth2Token && $this->providerKey === $token->getProviderKey();
    }

    private function getAuthenticatedUser(string $userIdentifier): ?UserInterface
    {
        if ('' === $userIdentifier) {
            /*
             * If the identifier is an empty string, that means that the
             * access token isn't bound to a user defined in the system.
             */
            return null;
        }

        return $this->userProvider->loadUserByUsername($userIdentifier);
    }
}
