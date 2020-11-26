<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use DateTimeImmutable;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var AccessToken[]
     */
    private $accessTokens = [];

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?AccessToken
    {
        return $this->accessTokens[$identifier] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(AccessToken $accessToken): void
    {
        $this->accessTokens[$accessToken->getIdentifier()] = $accessToken;
    }

    public function clearExpired(): int
    {
        $count = \count($this->accessTokens);

        $now = new DateTimeImmutable();
        $this->accessTokens = array_filter($this->accessTokens, static function (AccessToken $accessToken) use ($now): bool {
            return $accessToken->getExpiry() >= $now;
        });

        return $count - \count($this->accessTokens);
    }
}
