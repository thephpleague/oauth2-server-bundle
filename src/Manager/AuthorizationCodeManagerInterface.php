<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

/**
 * @method int clearExpiredTokens(?\DateInterval $expireCleanupDelay = null)
 */
interface AuthorizationCodeManagerInterface
{
    public function find(string $identifier): ?AuthorizationCodeInterface;

    public function save(AuthorizationCodeInterface $authCode): void;

    /**
     * @deprecated since 2.1. Use clearExpiredTokens() instead.
     */
    public function clearExpired(): int;
}
