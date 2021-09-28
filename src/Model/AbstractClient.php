<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

/**
 * @psalm-consistent-constructor
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
abstract class AbstractClient
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string|null
     */
    private $secret;

    /**
     * @var list<RedirectUri>
     */
    private $redirectUris = [];

    /**
     * @var list<Grant>
     */
    private $grants = [];

    /**
     * @var list<Scope>
     */
    private $scopes = [];

    /**
     * @var bool
     */
    private $active = true;

    /**
     * @var bool
     */
    private $allowPlainTextPkce = false;

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

    public function setName(string $name): self
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

    public function setRedirectUris(RedirectUri ...$redirectUris): self
    {
        /** @var list<RedirectUri> $redirectUris */
        $this->redirectUris = $redirectUris;

        return $this;
    }

    /**
     * @return list<Grant>
     *
     * @psalm-mutation-free
     */
    public function getGrants(): array
    {
        return $this->grants;
    }

    public function setGrants(Grant ...$grants): self
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

    public function setScopes(Scope ...$scopes): self
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

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function isConfidential(): bool
    {
        return !empty($this->secret);
    }

    /**
     * @psalm-mutation-free
     */
    public function isPlainTextPkceAllowed(): bool
    {
        return $this->allowPlainTextPkce;
    }

    public function setAllowPlainTextPkce(bool $allowPlainTextPkce): self
    {
        $this->allowPlainTextPkce = $allowPlainTextPkce;

        return $this;
    }
}
