<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

/**
 * @method int clearExpiredTokens(?\DateInterval $expireCleanupDelay = null)
 */
interface DeviceCodeManagerInterface
{
    public function find(string $identifier): ?DeviceCodeInterface;

    public function findByUserCode(string $code): ?DeviceCodeInterface;

    public function save(DeviceCodeInterface $deviceCode): void;

    /**
     * @deprecated since 2.1. Use clearExpiredTokens() instead.
     */
    public function clearExpired(): int;
}
