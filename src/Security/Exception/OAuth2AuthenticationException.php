<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class OAuth2AuthenticationException extends AuthenticationException implements HttpExceptionInterface
{
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(string $message, int $statusCode, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return ['WWW-Authenticate' => 'Bearer'];
    }
}
