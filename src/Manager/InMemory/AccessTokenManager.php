<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var array<string, AccessTokenInterface>
     */
    private array $accessTokens = [];

    public function __construct(
        private readonly bool $persistAccessToken,
    ) {
    }

    public function find(string $identifier): ?AccessTokenInterface
    {
        if (!$this->persistAccessToken) {
            return null;
        }

        return $this->accessTokens[$identifier] ?? null;
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        if (!$this->persistAccessToken) {
            return;
        }

        $this->accessTokens[$accessToken->getIdentifier()] = $accessToken;
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        if (!$this->persistAccessToken) {
            return 0;
        }

        $count = \count($this->accessTokens);

        $expiry = (new \DateTimeImmutable())->sub($expireCleanupDelay ?? new \DateInterval('PT0S'));
        $this->accessTokens = array_filter($this->accessTokens, static fn (AccessTokenInterface $accessToken): bool => $accessToken->getExpiry() >= $expiry);

        return $count - \count($this->accessTokens);
    }

    public function clearExpired(): int
    {
        return $this->clearExpiredTokens();
    }
}
