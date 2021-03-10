<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class AuthorizationRequestResolveEventFactory
{
    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(ScopeConverterInterface $scopeConverter, ClientManagerInterface $clientManager)
    {
        $this->scopeConverter = $scopeConverter;
        $this->clientManager = $clientManager;
    }

    public function fromAuthorizationRequest(AuthorizationRequest $authorizationRequest): AuthorizationRequestResolveEvent
    {
        $scopes = $this->scopeConverter->toDomainArray(array_values($authorizationRequest->getScopes()));

        $client = $this->clientManager->find($authorizationRequest->getClient()->getIdentifier());

        if (null === $client) {
            throw new \RuntimeException(sprintf('No client found for the given identifier \'%s\'.', $authorizationRequest->getClient()->getIdentifier()));
        }

        return new AuthorizationRequestResolveEvent($authorizationRequest, $scopes, $client);
    }
}
