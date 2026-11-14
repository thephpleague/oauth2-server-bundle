<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

interface ClientCredentialsUserFactoryInterface
{
    /**
     * @param non-empty-string $clientId
     */
    public function createUser(string $clientId): UserInterface;
}
