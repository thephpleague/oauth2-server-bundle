<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager;

use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

class FakeDeviceCodeManager implements DeviceCodeManagerInterface
{
    public function find(string $identifier): ?DeviceCodeInterface
    {
        return null;
    }

    public function findByUserCode(string $code): ?DeviceCodeInterface
    {
        return null;
    }

    public function save(DeviceCodeInterface $deviceCode, bool $persist = true): void
    {
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        return 0;
    }

    public function clearExpired(): int
    {
        return $this->clearExpiredTokens();
    }
}
