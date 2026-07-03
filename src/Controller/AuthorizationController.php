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

final class AuthorizationController
{
    private AuthorizationServer $server;

    private EventDispatcherInterface $eventDispatcher;

    private AuthorizationRequestResolveEventFactory $eventFactory;

    private UserConverterInterface $userConverter;

    private ClientManagerInterface $clientManager;

    private HttpMessageFactoryInterface $httpMessageFactory;

    private HttpFoundationFactoryInterface $httpFoundationFactory;

    private ResponseFactoryInterface $responseFactory;

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
            $authRequest = $this->server->validateAuthorizationRequest($serverRequest);

            if ('plain' === $authRequest->getCodeChallengeMethod()) {
                /** @var AbstractClient $client */
                $client = $this->clientManager->find($authRequest->getClient()->getIdentifier());
                if (!$client->isPlainTextPkceAllowed()) {
                    throw OAuthServerException::invalidRequest('code_challenge_method', 'Plain code challenge method is not allowed for this client');
                }
            }

            $event = $this->eventDispatcher->dispatch(
                $this->eventFactory->fromAuthorizationRequest($authRequest),
                OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE
            );

            if ($response = $event->getResponse()) {
                return $response;
            }

            $authRequest->setUser($this->userConverter->toLeague($event->getUser()));

            $authRequest->setAuthorizationApproved($event->getAuthorizationResolution());

            $response = $this->server->completeAuthorizationRequest($authRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            $response = $e->generateHttpResponse($serverResponse, str_contains($e->getRedirectUri() ?? '', '#'));
        }

        return $this->httpFoundationFactory->createResponse($response);
    }
}
