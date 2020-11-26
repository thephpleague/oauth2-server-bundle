<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class Grant
{
    private $grant;

    public function __construct(string $grant)
    {
        $this->grant = $grant;
    }

    public function __toString(): string
    {
        return $this->grant;
    }
}
