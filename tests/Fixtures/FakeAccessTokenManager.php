<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

class FakeAccessTokenManager implements AccessTokenManagerInterface
{
    public function find(string $identifier): ?AccessTokenInterface
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
