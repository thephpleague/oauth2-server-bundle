<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class RefreshToken implements RefreshTokenInterface
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
     * @var AccessTokenInterface|null
     */
    private $accessToken;

    /**
     * @var bool
     */
    private $revoked = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(string $identifier, \DateTimeInterface $expiry, AccessTokenInterface $accessToken = null)
    {
        $this->identifier = $identifier;
        $this->expiry = $expiry;
        $this->accessToken = $accessToken;
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
    public function getExpiry(): \DateTimeInterface
    {
        return $this->expiry;
    }

    /**
     * @psalm-mutation-free
     */
    public function getAccessToken(): ?AccessTokenInterface
    {
        return $this->accessToken;
    }

    /**
     * @psalm-mutation-free
     */
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
