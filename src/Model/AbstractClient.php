<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
abstract class AbstractClient implements ClientInterface
{
    private string $name;
    /** @var non-empty-string */
    protected string $identifier;
    private ?string $secret;

    /** @var list<RedirectUri> */
    private array $redirectUris = [];

    /** @var list<Grant> */
    private array $grants = [];

    /** @var list<Scope> */
    private array $scopes = [];

    private bool $active = true;
    private bool $allowPlainTextPkce = false;

    /**
     * @param non-empty-string $identifier
     */
    public function __construct(string $name, string $identifier, ?string $secret)
    {
        $this->name = $name;
        $this->identifier = $identifier;
        $this->secret = $secret;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ClientInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * @return list<RedirectUri>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    public function setRedirectUris(RedirectUri ...$redirectUris): ClientInterface
    {
        /** @var list<RedirectUri> $redirectUris */
        $this->redirectUris = $redirectUris;

        return $this;
    }

    public function getGrants(): array
    {
        return $this->grants;
    }

    public function setGrants(Grant ...$grants): ClientInterface
    {
        /** @var list<Grant> $grants */
        $this->grants = $grants;

        return $this;
    }

    /**
     * @return list<Scope>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(Scope ...$scopes): ClientInterface
    {
        /** @var list<Scope> $scopes */
        $this->scopes = $scopes;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): ClientInterface
    {
        $this->active = $active;

        return $this;
    }

    public function isConfidential(): bool
    {
        return null !== $this->secret && '' !== $this->secret;
    }

    public function isPlainTextPkceAllowed(): bool
    {
        return $this->allowPlainTextPkce;
    }

    public function setAllowPlainTextPkce(bool $allowPlainTextPkce): ClientInterface
    {
        $this->allowPlainTextPkce = $allowPlainTextPkce;

        return $this;
    }
}
