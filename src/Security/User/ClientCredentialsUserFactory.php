<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

final class ClientCredentialsUserFactory implements ClientCredentialsUserFactoryInterface
{
    public function createUser(string $clientId): UserInterface
    {
        return new ClientCredentialsUser($clientId);
    }
}
