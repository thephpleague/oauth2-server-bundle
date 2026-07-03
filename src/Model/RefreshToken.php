<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class RefreshToken implements \Stringable, RefreshTokenInterface
{
    private bool $revoked = false;

    public function __construct(
        private readonly string $identifier,
        private readonly \DateTimeInterface $expiry,
        private readonly ?AccessTokenInterface $accessToken = null,
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

    public function getAccessToken(): ?AccessTokenInterface
    {
        return $this->accessToken;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): RefreshTokenInterface
    {
        $this->revoked = true;

        return $this;
    }
}
