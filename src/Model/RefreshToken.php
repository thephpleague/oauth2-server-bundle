<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class RefreshToken implements RefreshTokenInterface
{
    private string $identifier;
    private \DateTimeInterface $expiry;
    private ?AccessTokenInterface $accessToken;
    private bool $revoked = false;

    public function __construct(string $identifier, \DateTimeInterface $expiry, ?AccessTokenInterface $accessToken = null)
    {
        $this->identifier = $identifier;
        $this->expiry = $expiry;
        $this->accessToken = $accessToken;
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
