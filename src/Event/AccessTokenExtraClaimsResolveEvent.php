<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class AccessTokenExtraClaimsResolveEvent extends Event
{
    /**
     * @var array<non-empty-string,mixed>
     */
    private array $extraClaims = [];

    /**
     * @param ScopeEntityInterface[] $scopes
     */
    public function __construct(
        private ClientEntityInterface $clientEntity,
        private array $scopes,
        private ?string $userIdentifier = null,
    ) {
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->clientEntity;
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    /**
     * @param array<non-empty-string, mixed> $extraClaims
     */
    public function setExtraClaims(array $extraClaims): self
    {
        $this->extraClaims = $extraClaims;

        return $this;
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function getExtraClaims(): array
    {
        return $this->extraClaims;
    }
}
