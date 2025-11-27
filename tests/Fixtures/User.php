<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class User extends \ArrayObject implements UserInterface
{
    public function __construct(private readonly ?string $userIdentifier = null)
    {
        parent::__construct();
    }

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

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier ?? FixtureFactory::FIXTURE_USER;
    }

    public function eraseCredentials(): void
    {
    }
}
