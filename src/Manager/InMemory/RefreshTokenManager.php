<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;
use Symfony\Component\Clock\ClockAwareTrait;

final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    use ClockAwareTrait;

    /**
     * @var array<string, RefreshTokenInterface>
     */
    private array $refreshTokens = [];

    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->refreshTokens[$identifier] ?? null;
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->refreshTokens[$refreshToken->getIdentifier()] = $refreshToken;
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        $count = \count($this->refreshTokens);

        $expiry = $this->now();
        if ($expireCleanupDelay) {
            $expiry = $expiry->sub($expireCleanupDelay);
        }
        $this->refreshTokens = array_filter($this->refreshTokens, static fn (RefreshTokenInterface $refreshToken): bool => $refreshToken->getExpiry() >= $expiry);

        return $count - \count($this->refreshTokens);
    }

    public function clearExpired(): int
    {
        trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Method "%s::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.', self::class);

        return $this->clearExpiredTokens();
    }
}
