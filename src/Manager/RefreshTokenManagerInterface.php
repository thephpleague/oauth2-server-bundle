<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

/**
 * @method int clearExpiredTokens(?\DateInterval $expireCleanupDelay = null)
 */
interface RefreshTokenManagerInterface
{
    public function find(string $identifier): ?RefreshTokenInterface;

    public function save(RefreshTokenInterface $refreshToken): void;

    /**
     * @deprecated since 2.1. Use clearExpiredTokens() instead.
     */
    public function clearExpired(): int;
}
