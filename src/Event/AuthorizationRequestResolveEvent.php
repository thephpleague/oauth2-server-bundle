<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Scope;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorizationRequestResolveEvent extends Event
{
    public const AUTHORIZATION_APPROVED = true;
    public const AUTHORIZATION_DENIED = false;

    /**
     * @var AuthorizationRequest
     */
    private $authorizationRequest;

    /**
     * @var Scope[]
     */
    private $scopes;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $authorizationResolution = self::AUTHORIZATION_DENIED;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var UserInterface|null
     */
    private $user;

    /**
     * @param Scope[] $scopes
     */
    public function __construct(AuthorizationRequest $authorizationRequest, array $scopes, Client $client)
    {
        $this->authorizationRequest = $authorizationRequest;
        $this->scopes = $scopes;
        $this->client = $client;
    }

    public function getAuthorizationResolution(): bool
    {
        return $this->authorizationResolution;
    }

    public function resolveAuthorization(bool $authorizationResolution): self
    {
        $this->authorizationResolution = $authorizationResolution;
        $this->response = null;
        $this->stopPropagation();

        return $this;
    }

    public function hasResponse(): bool
    {
        return $this->response instanceof ResponseInterface;
    }

    /**
     * @psalm-mutation-free
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }

    public function getGrantTypeId(): string
    {
        return $this->authorizationRequest->getGrantTypeId();
    }

    /**
     * @psalm-mutation-free
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Scope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isAuthorizationApproved(): bool
    {
        return $this->authorizationRequest->isAuthorizationApproved();
    }

    public function getRedirectUri(): ?string
    {
        return $this->authorizationRequest->getRedirectUri();
    }

    public function getState(): ?string
    {
        return $this->authorizationRequest->getState();
    }

    public function getCodeChallenge(): string
    {
        return $this->authorizationRequest->getCodeChallenge();
    }

    public function getCodeChallengeMethod(): string
    {
        return $this->authorizationRequest->getCodeChallengeMethod();
    }
}
