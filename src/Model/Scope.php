<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class Scope
{
    /**
     * @var string
     */
    private $scope;

    public function __construct(string $scope)
    {
        $this->scope = $scope;
    }

    public function __toString(): string
    {
        return $this->scope;
    }
}
