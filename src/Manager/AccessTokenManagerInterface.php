<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

/**
 * @method int clearExpiredTokens(?\DateInterval $expireCleanupDelay = null)
 */
interface AccessTokenManagerInterface
{
    public function find(string $identifier): ?AccessTokenInterface;

    public function save(AccessTokenInterface $accessToken): void;

    /**
     * @deprecated since 2.1. Use clearExpiredTokens() instead.
     */
    public function clearExpired(): int;
}
