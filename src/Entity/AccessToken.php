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

    public function initJwtConfiguration(): void
    {
        $this->traitInitJwtConfiguration();
        $builder = $this->jwtConfiguration->builder();

        if (!method_exists($this->jwtConfiguration, 'withBuilderFactory')) { // @phpstan-ignore function.alreadyNarrowedType
            $this->jwtConfiguration->setBuilderFactory(
                $this->createBuilderFactory($builder, $this->extraClaims)
            );

            return;
        }

        $this->jwtConfiguration = $this->jwtConfiguration->withBuilderFactory(
            $this->createBuilderFactory($builder, $this->extraClaims)
        );
    }

    /**
     * @param array<non-empty-string, mixed> $extraClaims
     *
     * @return \Closure(ClaimsFormatter): Builder
     */
    private function createBuilderFactory(Builder $builder, array $extraClaims): \Closure
    {
        return static function (ClaimsFormatter $claimFormatter) use ($builder, $extraClaims): Builder {
            foreach ($extraClaims as $name => $value) {
                if ('scopes' === $name) {
                    throw new \InvalidArgumentException('The "scopes" claim is reserved and cannot be used as an extra claim.');
                }
                $builder = $builder->withClaim($name, $value);
            }

            return $builder;
        };
    }
}
