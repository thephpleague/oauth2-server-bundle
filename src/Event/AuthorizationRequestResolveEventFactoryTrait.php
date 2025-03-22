<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
trait AuthorizationRequestResolveEventFactoryTrait
{
    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var Security
     */
    private $security;

    public function fromAuthorizationRequest(AuthorizationRequestInterface $authorizationRequest): AuthorizationRequestResolveEvent
    {
        $scopes = $this->scopeConverter->toDomainArray(array_values($authorizationRequest->getScopes()));

        $client = $this->clientManager->find($authorizationRequest->getClient()->getIdentifier());

        if (null === $client) {
            throw new \RuntimeException(\sprintf('No client found for the given identifier \'%s\'.', $authorizationRequest->getClient()->getIdentifier()));
        }

        $user = $this->security->getUser();
        if (null === $user) {
            throw new \RuntimeException('A logged in user is required to resolve the authorization request.');
        }

        return new AuthorizationRequestResolveEvent($authorizationRequest, $scopes, $client, $user);
    }
}
