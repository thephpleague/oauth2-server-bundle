<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

interface DeviceCodeInterface
{
    public function __toString(): string;

    public function getIdentifier(): string;

    public function getExpiry(): \DateTimeImmutable;

    public function getUserIdentifier(): ?string;

    public function setUserIdentifier(string $userIdentifier): self;

    public function getClient(): ClientInterface;

    /**
     * @return list<Scope>
     */
    public function getScopes(): array;

    public function isRevoked(): bool;

    public function revoke(): self;

    public function getUserCode(): string;

    public function getUserApproved(): bool;

    public function setUserApproved(bool $userApproved): self;

    public function getIncludeVerificationUriComplete(): bool;

    public function getVerificationUri(): string;

    public function getLastPolledAt(): ?\DateTimeImmutable;

    public function setLastPolledAt(\DateTimeImmutable $lastPolledAt): self;

    public function getInterval(): int;

}
