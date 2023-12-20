<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Null;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    public function find(string $identifier): ?AccessToken
    {
        return null;
    }

    public function save(AccessTokenInterface $accessToken): void
    {
    }

    public function clearExpired(): int
    {
        return 0;
    }
}
