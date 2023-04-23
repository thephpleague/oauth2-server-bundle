<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use DateTimeInterface;

interface AuthorizationCodeInterface
{
    public function __toString(): string;

    public function getIdentifier(): string;

    public function getExpiryDateTime(): DateTimeInterface;

    public function getUserIdentifier(): ?string;

    public function getClient(): ClientInterface;

    public function getScopes(): array;

    public function isRevoked(): bool;

    public function revoke(): self;
}
