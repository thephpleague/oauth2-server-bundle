<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class Scope
{
    /**
     * @var non-empty-string
     */
    private $scope;

    /**
     * @param non-empty-string $scope
     */
    public function __construct(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->scope;
    }
}
