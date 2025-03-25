<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class DeviceCode implements DeviceCodeInterface
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
     * @var ClientInterface
     */
    private $client;

    /**
     * @var list<Scope>
     */
    private $scopes;

    /**
     * @var bool
     */
    private $revoked = false;

    /**
     * @var string
     */
    private $userCode;

    /**
     * @var bool
     */
    private $userApproved;

    /**
     * @var bool
     */
    private $includeVerificationUriComplete;

    /**
     * @var string
     */
    private $verificationUri;

    /**
     * @var \DateTimeInterface|null
     */
    private $lastPolledAt;

    /**
     * @var int
     */
    private $interval;

    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        string $identifier,
        \DateTimeImmutable $expiry,
        ClientInterface $client,
        ?string $userIdentifier,
        array $scopes,
        string $userCode,
        bool $userApproved,
        bool $includeVerificationUriComplete,
        string $verificationUri,
        ?\DateTimeImmutable $lastPolledAt,
        int $interval
    ) {
        $this->identifier = $identifier;
        $this->expiry = $expiry;
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
        $this->scopes = $scopes;
        $this->userCode = $userCode;
        $this->userApproved = $userApproved;
        $this->includeVerificationUriComplete = $includeVerificationUriComplete;
        $this->verificationUri = $verificationUri;
        $this->lastPolledAt = $lastPolledAt;
        $this->interval = $interval;
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

    public function setUserIdentifier($userIdentifier): DeviceCodeInterface
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
