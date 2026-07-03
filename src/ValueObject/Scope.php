<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class Scope implements \Stringable
{
    /**
     * @param non-empty-string $scope
     */
    public function __construct(
        private readonly string $scope,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->scope;
    }
}
