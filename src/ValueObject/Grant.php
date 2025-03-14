<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class Grant
{
    /**
     * @var non-empty-string
     */
    private $grant;

    /**
     * @param non-empty-string $grant
     */
    public function __construct(string $grant)
    {
        $this->grant = $grant;
    }

    public function __toString(): string
    {
        return $this->grant;
    }
}
