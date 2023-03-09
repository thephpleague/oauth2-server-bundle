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
    private $authorizationCodes = [];

    /**
     * @psalm-mutation-free
     */
    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->authorizationCodes[$identifier] ?? null;
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->authorizationCodes[$authCode->getIdentifier()] = $authCode;
    }

    public function clearExpired(): int
    {
        $count = \count($this->authorizationCodes);

        $now = new \DateTimeImmutable();
        $this->authorizationCodes = array_filter($this->authorizationCodes, static function (AuthorizationCodeInterface $authorizationCode) use ($now): bool {
            return $authorizationCode->getExpiryDateTime() >= $now;
        });

        return $count - \count($this->authorizationCodes);
    }
}
