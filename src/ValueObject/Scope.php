<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

/**
 * @psalm-immutable
 */
class Scope
{
    /**
     * @var non-empty-string
     */
    private $scope;

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $scope
     */
    public function __construct(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @psalm-mutation-free
     *
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->scope;
    }
}
