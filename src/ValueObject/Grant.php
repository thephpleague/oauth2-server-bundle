<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class Grant implements \Stringable
{
    /**
     * @param non-empty-string $grant
     */
    public function __construct(
        private readonly string $grant,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->grant;
    }
}
