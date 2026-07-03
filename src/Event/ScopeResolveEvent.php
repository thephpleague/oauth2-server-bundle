<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\Event;

final class ScopeResolveEvent extends Event
{
    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        private array $scopes,
        private readonly Grant $grant,
        private readonly AbstractClient $client,
        private readonly int|string|null $userIdentifier,
    ) {
    }

    /**
     * @return list<Scope>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(Scope ...$scopes): self
    {
        /** @var list<Scope> $scopes */
        $this->scopes = $scopes;

        return $this;
    }

    public function getGrant(): Grant
    {
        return $this->grant;
    }

    public function getClient(): AbstractClient
    {
        return $this->client;
    }

    public function getUserIdentifier(): string|int|null
    {
        return $this->userIdentifier;
    }
}
