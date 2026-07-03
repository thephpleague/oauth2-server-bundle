<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class AccessToken implements \Stringable, AccessTokenInterface
{
    private bool $revoked = false;

    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        private readonly string $identifier,
        private readonly \DateTimeInterface $expiry,
        private readonly ClientInterface $client,
        private readonly ?string $userIdentifier,
        private readonly array $scopes,
    ) {
    }

    public function __toString(): string
    {
        return $this->getIdentifier();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExpiry(): \DateTimeInterface
    {
        return $this->expiry;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): AccessTokenInterface
    {
        $this->revoked = true;

        return $this;
    }
}
