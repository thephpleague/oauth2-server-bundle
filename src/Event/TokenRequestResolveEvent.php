<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

final class TokenRequestResolveEvent extends Event
{
    public function __construct(
        private Response $response,
    ) {
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }
}
