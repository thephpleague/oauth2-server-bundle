<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

/**
 * @psalm-immutable
 */
class Scope
{
    /**
     * @var string
     */
    private $scope;

    /**
     * @psalm-mutation-free
     */
    public function __construct(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return $this->scope;
    }
}
