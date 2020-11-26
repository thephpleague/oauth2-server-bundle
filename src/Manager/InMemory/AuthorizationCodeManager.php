<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use DateTimeImmutable;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var AuthorizationCode[]
     */
    private $authorizationCodes = [];

    public function find(string $identifier): ?AuthorizationCode
    {
        return $this->authorizationCodes[$identifier] ?? null;
    }

    public function save(AuthorizationCode $authorizationCode): void
    {
        $this->authorizationCodes[$authorizationCode->getIdentifier()] = $authorizationCode;
    }

    public function clearExpired(): int
    {
        $count = \count($this->authorizationCodes);

        $now = new DateTimeImmutable();
        $this->authorizationCodes = array_filter($this->authorizationCodes, static function (AuthorizationCode $authorizationCode) use ($now): bool {
            return $authorizationCode->getExpiryDateTime() >= $now;
        });

        return $count - \count($this->authorizationCodes);
    }
}
