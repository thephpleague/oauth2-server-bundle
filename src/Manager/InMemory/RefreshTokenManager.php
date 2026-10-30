<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

final class RefreshTokenManager implements RefreshTokenManagerInterface
{
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

        $expiry = (new \DateTimeImmutable())->sub($expireCleanupDelay ?? new \DateInterval('PT0S'));
        $this->refreshTokens = array_filter($this->refreshTokens, static fn (RefreshTokenInterface $refreshToken): bool => $refreshToken->getExpiry() >= $expiry);

        return $count - \count($this->refreshTokens);
    }

    public function clearExpired(): int
    {
        return $this->clearExpiredTokens();
    }
}
