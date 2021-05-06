<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\Bundle\OAuth2ServerBundle\Entity\Scope as ScopeEntity;
use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

interface ScopeConverterInterface
{
    public function toDomain(ScopeEntityInterface $scope): ScopeModel;

    /**
     * @param list<ScopeEntityInterface> $scopes
     *
     * @return list<ScopeModel>
     */
    public function toDomainArray(array $scopes): array;

    public function toLeague(ScopeModel $scope): ScopeEntity;

    /**
     * @param list<ScopeModel> $scopes
     *
     * @return list<ScopeEntity>
     */
    public function toLeagueArray(array $scopes): array;
}
