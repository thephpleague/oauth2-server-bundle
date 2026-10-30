<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

interface RefreshTokenManagerInterface extends ExpiredTokenClearerInterface
{
    public function find(string $identifier): ?RefreshTokenInterface;

    public function save(RefreshTokenInterface $refreshToken): void;
}
