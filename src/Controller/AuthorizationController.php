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
    public function __construct(
        private readonly AuthorizationServer $server,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthorizationRequestResolveEventFactory $eventFactory,
        private readonly UserConverterInterface $userConverter,
        private readonly ClientManagerInterface $clientManager,
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
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
