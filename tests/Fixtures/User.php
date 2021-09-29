<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class User extends \ArrayObject implements UserInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this['roles'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): ?string
    {
        return FixtureFactory::FIXTURE_PASSWORD;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier(): string
    {
        return FixtureFactory::FIXTURE_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
