<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class ClientFilter
{
    /**
     * @var list<Grant>
     */
    private $grants = [];

    /**
     * @var list<RedirectUri>
     */
    private $redirectUris = [];

    /**
     * @var list<Scope>
     */
    private $scopes = [];

    /**
     * @psalm-pure
     */
    public static function create(): self
    {
        return new static();
    }

    public function addGrantCriteria(Grant ...$grants): self
    {
        foreach ($grants as $grant) {
            $this->grants[] = $grant;
        }

        return $this;
    }

    public function addRedirectUriCriteria(RedirectUri ...$redirectUris): self
    {
        foreach ($redirectUris as $redirectUri) {
            $this->redirectUris[] = $redirectUri;
        }

        return $this;
    }

    public function addScopeCriteria(Scope ...$scopes): self
    {
        foreach ($scopes as $scope) {
            $this->scopes[] = $scope;
        }

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

    /**
     * @return list<RedirectUri>
     *
     * @psalm-mutation-free
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
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

    /**
     * @psalm-mutation-free
     */
    public function hasFilters(): bool
    {
        return
            !empty($this->grants)
            || !empty($this->redirectUris)
            || !empty($this->scopes);
    }
}
