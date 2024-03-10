<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

class FakeRefreshTokenManager implements RefreshTokenManagerInterface
{
    public function find(string $identifier): ?RefreshTokenInterface
    {
        return null;
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
    }

    public function clearExpired(): int
    {
        return 0;
    }
}
