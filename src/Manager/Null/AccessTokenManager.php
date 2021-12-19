<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Null;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    public function find(string $identifier): ?AccessToken
    {
        return null;
    }

    public function save(AccessToken $accessToken): void
    {
    }

    public function clearExpired(): int
    {
        return 0;
    }
}
