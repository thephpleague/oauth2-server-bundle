<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

final class TokenRequestResolveEvent extends Event
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
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

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }
}
