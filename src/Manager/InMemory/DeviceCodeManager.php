<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

final class DeviceCodeManager implements DeviceCodeManagerInterface
{
    /**
     * @var array<string, DeviceCodeInterface>
     */
    private array $deviceCodes = [];

    public function find(string $identifier): ?DeviceCodeInterface
    {
        return $this->deviceCodes[$identifier] ?? null;
    }

    public function findByUserCode(string $code): ?DeviceCodeInterface
    {
        foreach ($this->deviceCodes as $deviceCode) {
            if ($deviceCode->getUserCode() === $code) {
                return $deviceCode;
            }
        }

        return null;
    }

    public function save(DeviceCodeInterface $deviceCode): void
    {
        $this->deviceCodes[$deviceCode->getIdentifier()] = $deviceCode;
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        $count = \count($this->deviceCodes);

        $expiry = (new \DateTimeImmutable());
        if ($expireCleanupDelay) {
            $expiry = $expiry->sub($expireCleanupDelay);
        }
        $this->deviceCodes = array_filter($this->deviceCodes, static fn (DeviceCodeInterface $accessToken): bool => $accessToken->getExpiry() >= $expiry);

        return $count - \count($this->deviceCodes);
    }

    public function clearExpired(): int
    {
        trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Method "%s::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.', self::class);

        return $this->clearExpiredTokens();
    }
}
