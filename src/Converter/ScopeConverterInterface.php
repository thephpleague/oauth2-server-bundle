<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\Bundle\OAuth2ServerBundle\League\Entity\Scope as ScopeEntity;
use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;

interface ScopeConverterInterface
{
    public function toDomain(ScopeEntity $scope): ScopeModel;

    /**
     * @param ScopeEntity[] $scopes
     *
     * @return ScopeModel[]
     */
    public function toDomainArray(array $scopes): array;

    public function toLeague(ScopeModel $scope): ScopeEntity;

    /**
     * @param ScopeModel[] $scopes
     *
     * @return ScopeEntity[]
     */
    public function toLeagueArray(array $scopes): array;
}
