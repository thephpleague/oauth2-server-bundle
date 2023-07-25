<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service;

use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * Service responsible for revoking credentials on client-level and user-level.
 * Credentials = access tokens, refresh tokens and authorization codes.
 */
interface CredentialsRevokerInterface
{
    public function revokeCredentialsForUser(UserEntityInterface $user): void;

    public function revokeCredentialsForClient(AbstractClient $client): void;
}
