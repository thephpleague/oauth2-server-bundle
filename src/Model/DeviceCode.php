<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class DeviceCode implements DeviceCodeInterface
{
    private bool $revoked = false;

    private bool $includeVerificationUriComplete = false;

    /**
     * @param non-empty-string $identifier
     * @param non-empty-string|null $userIdentifier
     * @param list<Scope> $scopes
     */
    public function __construct(
        private readonly string $identifier,
        private readonly \DateTimeImmutable $expiry,
        private readonly ClientInterface $client,
        private ?string $userIdentifier,
        private readonly array $scopes,
        private readonly string $userCode,
        private bool $userApproved,
        private readonly string $verificationUri,
        private ?\DateTimeImmutable $lastPolledAt,
        private readonly int $interval,
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

    public function getExpiry(): \DateTimeImmutable
    {
        return $this->expiry;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): DeviceCodeInterface
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
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

    public function revoke(): DeviceCodeInterface
    {
        $this->revoked = true;

        return $this;
    }

    public function getUserCode(): string
    {
        return $this->userCode;
    }

    public function getUserApproved(): bool
    {
        return $this->userApproved;
    }

    public function setUserApproved(bool $userApproved): DeviceCodeInterface
    {
        $this->userApproved = $userApproved;

        return $this;
    }

    public function getIncludeVerificationUriComplete(): bool
    {
        return $this->includeVerificationUriComplete;
    }

    public function getVerificationUri(): string
    {
        return $this->verificationUri;
    }

    public function getLastPolledAt(): ?\DateTimeImmutable
    {
        return $this->lastPolledAt;
    }

    public function setLastPolledAt(\DateTimeImmutable $lastPolledAt): DeviceCodeInterface
    {
        $this->lastPolledAt = $lastPolledAt;

        return $this;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }
}
