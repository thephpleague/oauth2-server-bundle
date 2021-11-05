<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class OAuth2Token extends AbstractToken
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        ?UserInterface $user,
        string $accessTokenId,
        string $oauthClientId,
        array $scopes,
        string $rolePrefix
    ) {
        $this->setAttribute('access_token_id', $accessTokenId);
        $this->setAttribute('oauth_client_id', $oauthClientId);
        $this->setAttribute('scopes', $scopes);

        // Build roles from scope
        $roles = array_map(function (string $scope) use ($rolePrefix): string {
            return strtoupper(trim(sprintf('%s%s', $rolePrefix, $scope)));
        }, $scopes);

        if (null !== $user) {
            // Merge the user's roles with the OAuth 2.0 scopes.
            $roles = array_merge($roles, $user->getRoles());
            $this->setUser($user);
        }

        parent::__construct(array_unique($roles));
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        /** @var list<string> $scopes */
        $scopes = $this->getAttribute('scopes');

        return $scopes;
    }

    public function getCredentials(): string
    {
        /** @var string */
        return $this->getAttribute('access_token_id');
    }

    public function getOAuthClientId(): string
    {
        /** @var string */
        return $this->getAttribute('oauth_client_id');
    }
}
