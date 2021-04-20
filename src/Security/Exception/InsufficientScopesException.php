<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Exception;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class InsufficientScopesException extends OAuth2AuthenticationException
{
    public static function create(TokenInterface $token, ?\Throwable $previous = null): self
    {
        $exception = new self('The token has insufficient scopes.', 403, $previous);
        $exception->setToken($token);

        return $exception;
    }
}
