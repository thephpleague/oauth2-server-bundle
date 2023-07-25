<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\User;

use League\OAuth2\Server\Entities\UserEntityInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @psalm-immutable
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NullUser implements UserInterface, UserEntityInterface
{
    public function getIdentifier(): string
    {
        return '';
    }

    /**
     * @psalm-mutation-free
     */
    public function getUsername(): string
    {
        return '';
    }

    public function getUserIdentifier(): string
    {
        return '';
    }

    /**
     * @psalm-mutation-free
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * @psalm-mutation-free
     */
    public function eraseCredentials(): void
    {
        return;
    }
}
