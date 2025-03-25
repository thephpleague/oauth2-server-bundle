<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Controller;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DeviceCodeController
{
    /**
     * @var AuthorizationServer
     */
    private $server;

    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;

    /**
     * @var HttpFoundationFactoryInterface
     */
    private $httpFoundationFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(
        AuthorizationServer $server,
        HttpMessageFactoryInterface $httpMessageFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        ResponseFactoryInterface $responseFactory,
    ) {
        $this->server = $server;
        $this->httpMessageFactory = $httpMessageFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->responseFactory = $responseFactory;
    }

    public function indexAction(Request $request): Response
    {
        $serverRequest = $this->httpMessageFactory->createRequest($request);
        $serverResponse = $this->responseFactory->createResponse();

        try {
            $response = $this->server->respondToDeviceAuthorizationRequest($serverRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            $response = $e->generateHttpResponse($serverResponse);
        }

        return $this->httpFoundationFactory->createResponse($response);
    }
}
