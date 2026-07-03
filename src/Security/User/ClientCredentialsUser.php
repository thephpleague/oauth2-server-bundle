<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class ClientCredentialsUser implements UserInterface
{
    /**
     * @param non-empty-string $clientId
     */
    public function __construct(
        private readonly string $clientId,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->clientId;
    }

    public function getRoles(): array
    {
        return [];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
