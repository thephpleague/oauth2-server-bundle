<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

final class OAuth2Token extends AbstractToken
{
    /**
     * @var string
     */
    private $providerKey;

    public function __construct(
        ?UserInterface $user,
        array $scopes,
        string $clientId,
        string $accessTokenId,
        string $rolePrefix,
        string $providerKey
    ) {
        $this->setAttribute('role_prefix', $rolePrefix);

        $this->setAttribute('oauth_user_id', $user ? $user->getUsername() : null);
        $this->setAttribute('oauth_scopes', $scopes);
        $this->setAttribute('oauth_client_id', $clientId);
        $this->setAttribute('oauth_access_token_id', $accessTokenId);

        $roles = $this->buildRolesFromScopes();

        if (null !== $user) {
            // Merge the user's roles with the OAuth 2.0 scopes.
            $roles = array_merge($roles, $user->getRoles());

            $this->setUser($user);
        }

        parent::__construct(array_unique($roles));

        $this->providerKey = $providerKey;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->getAttribute('oauth_scopes');
    }

    public function getClientIdentifier(): string
    {
        return $this->getAttribute('oauth_client_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->getAttribute('oauth_access_token_id');
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function __serialize(): array
    {
        return [$this->providerKey, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        /** @var mixed[] $parentData */
        [$this->providerKey, $parentData] = $data;
        parent::__unserialize($parentData);
    }

    /**
     * @return string[]
     */
    private function buildRolesFromScopes(): array
    {
        /** @var string $prefix */
        $prefix = $this->getAttribute('role_prefix');
        $roles = [];

        /** @var list<string> $scopes */
        $scopes = $this->getAttribute('oauth_scopes');
        foreach ($scopes as $scope) {
            $roles[] = strtoupper(trim($prefix . $scope));
        }

        return $roles;
    }
}
