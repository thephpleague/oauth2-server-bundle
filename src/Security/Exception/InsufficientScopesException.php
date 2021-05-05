<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Exception;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class InsufficientScopesException extends OAuth2AuthenticationException
{
    public static function create(?\Throwable $previous = null): self
    {
        return new self('Insufficient scopes.', 403, $previous);
    }
}
