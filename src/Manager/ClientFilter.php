<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class ClientFilter
{
    /**
     * @var Grant[]
     */
    private $grants = [];

    /**
     * @var RedirectUri[]
     */
    private $redirectUris = [];

    /**
     * @var Scope[]
     */
    private $scopes = [];

    public static function create(): self
    {
        return new static();
    }

    public function addGrantCriteria(Grant ...$grants): self
    {
        return $this->addCriteria($this->grants, ...$grants);
    }

    public function addRedirectUriCriteria(RedirectUri ...$redirectUris): self
    {
        return $this->addCriteria($this->redirectUris, ...$redirectUris);
    }

    public function addScopeCriteria(Scope ...$scopes): self
    {
        return $this->addCriteria($this->scopes, ...$scopes);
    }

    private function addCriteria(&$field, ...$values): self
    {
        if (0 === \count($values)) {
            return $this;
        }

        $field = array_merge($field, $values);

        return $this;
    }

    /**
     * @return Grant[]
     */
    public function getGrants(): array
    {
        return $this->grants;
    }

    /**
     * @return RedirectUri[]
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @return Scope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasFilters(): bool
    {
        return
            !empty($this->grants)
            || !empty($this->redirectUris)
            || !empty($this->scopes);
    }
}
