<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var array<string, AccessToken>
     */
    private $accessTokens = [];

    /** @var bool */
    private $persistAccessToken;

    public function __construct(bool $persistAccessToken)
    {
        $this->persistAccessToken = $persistAccessToken;
    }

    /**
     * @psalm-mutation-free
     */
    public function find(string $identifier): ?AccessToken
    {
        if (!$this->persistAccessToken) {
            return null;
        }

        return $this->accessTokens[$identifier] ?? null;
    }

    public function save(AccessToken $accessToken): void
    {
        if (!$this->persistAccessToken) {
            return;
        }

        $this->accessTokens[$accessToken->getIdentifier()] = $accessToken;
    }

    public function clearExpired(): int
    {
        if (!$this->persistAccessToken) {
            return 0;
        }

        $count = \count($this->accessTokens);

        $now = new \DateTimeImmutable();
        $this->accessTokens = array_filter($this->accessTokens, static function (AccessToken $accessToken) use ($now): bool {
            return $accessToken->getExpiry() >= $now;
        });

        return $count - \count($this->accessTokens);
    }
}
