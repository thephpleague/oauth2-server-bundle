<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\Bundle\OAuth2ServerBundle\Entity\Scope as ScopeEntity;
use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

final class ScopeConverter implements ScopeConverterInterface
{
    public function toDomain(ScopeEntityInterface $scope): ScopeModel
    {
        return new ScopeModel($scope->getIdentifier());
    }

    /**
     * @param list<ScopeEntityInterface> $scopes
     *
     * @return list<ScopeModel>
     */
    public function toDomainArray(array $scopes): array
    {
        return array_map(function (ScopeEntityInterface $scope): ScopeModel {
            return $this->toDomain($scope);
        }, $scopes);
    }

    public function toLeague(ScopeModel $scope): ScopeEntity
    {
        $scopeEntity = new ScopeEntity();
        $scopeEntity->setIdentifier((string) $scope);

        return $scopeEntity;
    }

    /**
     * @param list<ScopeModel> $scopes
     *
     * @return list<ScopeEntity>
     */
    public function toLeagueArray(array $scopes): array
    {
        return array_map(function (ScopeModel $scope): ScopeEntity {
            return $this->toLeague($scope);
        }, $scopes);
    }
}
