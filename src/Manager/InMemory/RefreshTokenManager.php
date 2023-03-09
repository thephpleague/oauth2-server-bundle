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
    private $refreshTokens = [];

    /**
     * @psalm-mutation-free
     */
    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->refreshTokens[$identifier] ?? null;
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->refreshTokens[$refreshToken->getIdentifier()] = $refreshToken;
    }

    public function clearExpired(): int
    {
        $count = \count($this->refreshTokens);

        $now = new \DateTimeImmutable();
        $this->refreshTokens = array_filter($this->refreshTokens, static function (RefreshTokenInterface $refreshToken) use ($now): bool {
            return $refreshToken->getExpiry() >= $now;
        });

        return $count - \count($this->refreshTokens);
    }
}
