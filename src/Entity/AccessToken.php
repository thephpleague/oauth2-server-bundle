<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Entity;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\ClaimsFormatter;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

final class AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait {
        initJwtConfiguration as private traitInitJwtConfiguration;
    }
    use EntityTrait;
    use TokenEntityTrait;

    /**
     * @param array<non-empty-string, mixed> $extraClaims
     */
    public function __construct(
        private array $extraClaims = [],
    ) {
    }

    protected function withJwtBuilder(Builder $builder): Builder
    {
        foreach ($this->extraClaims as $name => $value) {
            if ('scopes' === $name) {
                throw new \InvalidArgumentException('The "scopes" claim is reserved and cannot be used as an extra claim.');
            }
            $builder = $builder->withClaim($name, $value);
        }

        return $builder;
    }

    public function initJwtConfiguration(): void
    {
        $this->traitInitJwtConfiguration();

        $builderWithExtraClaims = $this->withJwtBuilder($this->jwtConfiguration->builder());

        $this->jwtConfiguration = $this->jwtConfiguration->withBuilderFactory(
            static fn (ClaimsFormatter $claimFormatter): Builder => $builderWithExtraClaims
        );
    }
}
