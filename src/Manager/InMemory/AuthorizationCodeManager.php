<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var array<string, AuthorizationCodeInterface>
     */
    private array $authorizationCodes = [];

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->authorizationCodes[$identifier] ?? null;
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->authorizationCodes[$authCode->getIdentifier()] = $authCode;
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        $count = \count($this->authorizationCodes);

        $expiry = (new \DateTimeImmutable())->sub($expireCleanupDelay ?? new \DateInterval('PT0S'));
        $this->authorizationCodes = array_filter($this->authorizationCodes, static fn (AuthorizationCodeInterface $authorizationCode): bool => $authorizationCode->getExpiryDateTime() >= $expiry);

        return $count - \count($this->authorizationCodes);
    }

    public function clearExpired(): int
    {
        return $this->clearExpiredTokens();
    }
}
