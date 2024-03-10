<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

/**
 * @psalm-consistent-constructor
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
abstract class AbstractClient implements ClientInterface
{
    private string $name;
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
     * @psalm-mutation-free
     */
    public function __construct(string $name, string $identifier, ?string $secret)
    {
        $this->name = $name;
        $this->identifier = $identifier;
        $this->secret = $secret;
    }

    /**
     * @psalm-mutation-free
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ClientInterface
    {
        $this->name = $name;

        return $this;
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
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * @return list<RedirectUri>
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
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

    /**
     * @psalm-mutation-free
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): ClientInterface
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function isConfidential(): bool
    {
        return null !== $this->secret && '' !== $this->secret;
    }

    /**
     * @psalm-mutation-free
     */
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
