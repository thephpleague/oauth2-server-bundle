<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class Grant implements \Stringable
{
    /**
     * @var non-empty-string
     */
    private string $grant;

    /**
     * @param non-empty-string $grant
     */
    public function __construct(string $grant)
    {
        $this->grant = $grant;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->grant;
    }
}
