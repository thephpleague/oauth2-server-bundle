<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class AuthorizationCode
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var \DateTimeInterface
     */
    private $expiry;

    /**
     * @var string|null
     */
    private $userIdentifier;

    /**
     * @var AbstractClient
     */
    private $client;

    /**
     * @var list<Scope>
     */
    private $scopes = [];

    /**
     * @var bool
     */
    private $revoked = false;

    /**
     * @param list<Scope> $scopes
     *
     * @psalm-mutation-free
     */
    public function __construct(
        string $identifier,
        \DateTimeInterface $expiry,
        AbstractClient $client,
        ?string $userIdentifier,
        array $scopes
    ) {
        $this->identifier = $identifier;
        $this->expiry = $expiry;
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
        $this->scopes = $scopes;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return $this->getIdentifier();
    }

    /**
     * @psalm-mutation-free
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @psalm-mutation-free
     */
    public function getExpiryDateTime(): \DateTimeInterface
    {
        return $this->expiry;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    /**
     * @psalm-mutation-free
     */
    public function getClient(): AbstractClient
    {
        return $this->client;
    }

    /**
     * @return list<Scope>
     *
     * @psalm-mutation-free
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @psalm-mutation-free
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): self
    {
        $this->revoked = true;

        return $this;
    }
}
