<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class ScopeManager implements ScopeManagerInterface
{
    /**
     * @var Scope[]
     */
    private $scopes = [];

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?Scope
    {
        return $this->scopes[$identifier] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Scope $scope): void
    {
        $this->scopes[(string) $scope] = $scope;
    }
}
