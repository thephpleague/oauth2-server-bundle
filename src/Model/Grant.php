<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

/**
 * @psalm-immutable
 */
class Grant
{
    /**
     * @var string
     */
    private $grant;

    /**
     * @psalm-mutation-free
     */
    public function __construct(string $grant)
    {
        $this->grant = $grant;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return $this->grant;
    }
}
