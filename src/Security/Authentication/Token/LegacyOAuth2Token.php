<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Token;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

final class LegacyOAuth2Token extends AbstractToken
{
    /**
     * @var string
     */
    private $providerKey;

    public function __construct(
        ServerRequestInterface $serverRequest,
        ?UserInterface $user,
        string $rolePrefix,
        string $providerKey
    ) {
        $this->setAttribute('server_request', $serverRequest);
        $this->setAttribute('role_prefix', $rolePrefix);

        $roles = $this->buildRolesFromScopes();

        if (null !== $user) {
            // Merge the user's roles with the OAuth 2.0 scopes.
            $roles = array_merge($roles, $user->getRoles());

            $this->setUser($user);
        }

        parent::__construct(array_unique($roles));

        $this->providerKey = $providerKey;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        /** @var ServerRequestInterface */
        return $this->getAttribute('server_request');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->getServerRequest()->getAttribute('oauth_access_token_id');
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

        /** @var string[] $scopes */
        $scopes = $this->getServerRequest()->getAttribute('oauth_scopes', []);
        foreach ($scopes as $scope) {
            $roles[] = strtoupper(trim($prefix . $scope));
        }

        return $roles;
    }
}
