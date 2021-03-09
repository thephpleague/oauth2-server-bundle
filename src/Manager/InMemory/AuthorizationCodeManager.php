<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var array<string, AuthorizationCode>
     */
    private $authorizationCodes = [];

    /**
     * @psalm-mutation-free
     */
    public function find(string $identifier): ?AuthorizationCode
    {
        return $this->authorizationCodes[$identifier] ?? null;
    }

    public function save(AuthorizationCode $authCode): void
    {
        $this->authorizationCodes[$authCode->getIdentifier()] = $authCode;
    }

    public function clearExpired(): int
    {
        $count = \count($this->authorizationCodes);

        $now = new \DateTimeImmutable();
        $this->authorizationCodes = array_filter($this->authorizationCodes, static function (AuthorizationCode $authorizationCode) use ($now): bool {
            return $authorizationCode->getExpiryDateTime() >= $now;
        });

        return $count - \count($this->authorizationCodes);
    }
}
