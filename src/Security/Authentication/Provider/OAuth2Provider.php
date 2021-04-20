<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Provider;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\LegacyOAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\LegacyOAuth2TokenFactory;
use League\Bundle\OAuth2ServerBundle\Security\User\NullUser;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
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
     * @var LegacyOAuth2TokenFactory
     */
    private $oauth2TokenFactory;

    /**
     * @var string
     */
    private $providerKey;

    public function __construct(
        UserProviderInterface $userProvider,
        ResourceServer $resourceServer,
        LegacyOAuth2TokenFactory $oauth2TokenFactory,
        string $providerKey
    ) {
        $this->userProvider = $userProvider;
        $this->resourceServer = $resourceServer;
        $this->oauth2TokenFactory = $oauth2TokenFactory;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new \RuntimeException(sprintf('This authentication provider can only handle tokens of type \'%s\'.', LegacyOAuth2Token::class));
        }

        try {
            $request = $this->resourceServer->validateAuthenticatedRequest(
                $token->getServerRequest()
            );
        } catch (OAuthServerException $e) {
            throw new AuthenticationException('The resource server rejected the request.', 0, $e);
        }

        $user = $this->getAuthenticatedUser(
            (string) $request->getAttribute('oauth_user_id')
        );

        $token = $this->oauth2TokenFactory->createOAuth2Token($request, $user, $this->providerKey);
        $token->setAuthenticated(true);

        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-assert-if-true LegacyOAuth2Token $token
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof LegacyOAuth2Token && $this->providerKey === $token->getProviderKey();
    }

    private function getAuthenticatedUser(string $userIdentifier): UserInterface
    {
        if ('' === $userIdentifier) {
            /*
             * If the identifier is an empty string, that means that the
             * access token isn't bound to a user defined in the system.
             */
            return new NullUser();
        }

        return $this->userProvider->loadUserByUsername($userIdentifier);
    }
}
