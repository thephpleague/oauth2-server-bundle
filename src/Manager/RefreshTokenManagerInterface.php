<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

interface RefreshTokenManagerInterface
{
    public function find(string $identifier): ?RefreshToken;

    public function save(RefreshToken $refreshToken): void;

    public function clearExpired(): int;
}
