<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

/**
 * @method int clearExpiredTokens(?\DateInterval $expireCleanupDelay = null)
 */
interface ExpiredTokenClearerInterface
{
    /* public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int; */

    /**
     * @deprecated since 2.1. Use clearExpiredTokens() instead.
     */
    public function clearExpired(): int;
}
