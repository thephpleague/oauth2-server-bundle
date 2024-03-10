<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Exception;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class OAuth2AuthenticationFailedException extends OAuth2AuthenticationException
{
    public static function create(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 401, $previous);
    }
}
