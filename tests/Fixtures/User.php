<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class User extends \ArrayObject implements UserInterface
{
    public function getRoles(): array
    {
        return $this['roles'] ?? [];
    }

    public function getPassword(): ?string
    {
        return FixtureFactory::FIXTURE_PASSWORD;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return FixtureFactory::FIXTURE_USER;
    }

    public function eraseCredentials(): void
    {
    }
}
