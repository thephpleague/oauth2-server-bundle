<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Controller;

use League\Bundle\OAuth2ServerBundle\Event\TokenRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class TokenController
{
    public function __construct(
        private AuthorizationServer $server,
        private HttpMessageFactoryInterface $httpMessageFactory,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private ResponseFactoryInterface $responseFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $serverRequest = $this->httpMessageFactory->createRequest($request);
        $serverResponse = $this->responseFactory->createResponse();

        try {
            $response = $this->server->respondToAccessTokenRequest($serverRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            $response = $e->generateHttpResponse($serverResponse);
        }

        $renderedResponse = $this->httpFoundationFactory->createResponse($response);

        $event = $this->eventDispatcher->dispatch(
            new TokenRequestResolveEvent($renderedResponse),
            OAuth2Events::TOKEN_REQUEST_RESOLVE
        );

        return $event->getResponse();
    }
}
