<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

/**
 * @method string getName()
 */
interface ClientInterface
{
    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string;

    public function getSecret(): ?string;

    /**
     * @return list<RedirectUri>
     */
    public function getRedirectUris(): array;

    public function setRedirectUris(RedirectUri ...$redirectUris): self;

    /**
     * @return list<Grant>
     */
    public function getGrants(): array;

    public function setGrants(Grant ...$grants): self;

    /**
     * @return list<Scope>
     */
    public function getScopes(): array;

    public function setScopes(Scope ...$scopes): self;

    public function isActive(): bool;

    public function setActive(bool $active): self;

    public function isConfidential(): bool;

    public function isPlainTextPkceAllowed(): bool;

    public function setAllowPlainTextPkce(bool $allowPlainTextPkce): self;
}
