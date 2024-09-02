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
        ResponseFactoryInterface $responseFactory
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

            $authRequest->setUser($this->userConverter->toLeague($event->getUser()));

            if ($response = $event->getResponse()) {
                return $response;
            }

            $authRequest->setAuthorizationApproved($event->getAuthorizationResolution());

            $response = $this->server->completeAuthorizationRequest($authRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            // https://datatracker.ietf.org/doc/html/rfc6749#section-4.1.2.1
            //       If the request fails due to a missing, invalid, or mismatching
            //       redirection URI, or if the client identifier is missing or invalid,
            //       the authorization server SHOULD inform the resource owner of the
            //       error and MUST NOT automatically redirect the user-agent to the
            //       invalid redirection URI.
            //
            //       If the resource owner denies the access request or if the request
            //       fails for reasons other than a missing or invalid redirection URI,
            //       the authorization server informs the client by adding the following
            //       parameters to the query component of the redirection URI using the
            //       "application/x-www-form-urlencoded" format
            //
            // so if redirectUri is not already set, we try to set request redirect_uri params, fallback to first redirectUri of client
            /** @psalm-suppress RiskyTruthyFalsyComparison !empty($e->getHint()),empty($e->getRedirectUri()) we really want to check null and empty */
            if (!empty($client)
                && ('invalid_client' === $e->getErrorType()
                    || ('invalid_request' === $e->getErrorType() && !empty($e->getHint())
                        && !\in_array(sscanf($e->getHint() ?? '', 'Check the `%s` parameter')[0] ?? null, ['client_id', 'client_secret', 'redirect_uri'])))
                && empty($e->getRedirectUri())) {
                /** @var \League\Bundle\OAuth2ServerBundle\Model\ClientInterface $client */
                $redirectUri = $request->query->get('redirect_uri',     // query string has priority
                    (string)$request->request->get('redirect_uri',              // then we check body to support POST request
                        $client->getRedirectUris()[0]?->__toString() ?? ''));   // then first client redirect uri
                if (!empty($redirectUri)) {
                    $e = new OAuthServerException(
                        $e->getMessage(),
                        $e->getCode(),
                        $e->getErrorType(),
                        $e->getHttpStatusCode(),
                        $e->getHint(),
                        $redirectUri,
                        $e->getPrevious(),
                    );
                }
            }
            $response = $e->generateHttpResponse($serverResponse);
        }

        return $this->httpFoundationFactory->createResponse($response);
    }
}
