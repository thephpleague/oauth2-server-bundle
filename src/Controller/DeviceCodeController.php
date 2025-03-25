<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Controller;

use League\Bundle\OAuth2ServerBundle\Converter\UserConverterInterface;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEventFactory;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeviceCodeController
{
    /**
     * @var AuthorizationServer
     */
    private $server;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var AuthorizationRequestResolveEventFactory
     */
    private $eventFactory;

    /**
     * @var UserConverterInterface
     */
    private $userConverter;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

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
        EventDispatcherInterface $eventDispatcher,
        AuthorizationRequestResolveEventFactory $eventFactory,
        UserConverterInterface $userConverter,
        ClientManagerInterface $clientManager,
        HttpMessageFactoryInterface $httpMessageFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        ResponseFactoryInterface $responseFactory,
    ) {
        $this->server = $server;
        $this->eventDispatcher = $eventDispatcher;
        $this->eventFactory = $eventFactory;
        $this->userConverter = $userConverter;
        $this->clientManager = $clientManager;
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
