<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorizationRequestResolveEvent extends Event
{
    public const AUTHORIZATION_APPROVED = true;
    public const AUTHORIZATION_DENIED = false;

    /**
     * @var AuthorizationRequestInterface
     */
    private $authorizationRequest;

    /**
     * @var Scope[]
     */
    private $scopes;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var bool
     */
    private $authorizationResolution = self::AUTHORIZATION_DENIED;

    /**
     * @var Response|null
     */
    private $response;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @param Scope[] $scopes
     */
    public function __construct(AuthorizationRequestInterface $authorizationRequest, array $scopes, ClientInterface $client, UserInterface $user)
    {
        $this->authorizationRequest = $authorizationRequest;
        $this->scopes = $scopes;
        $this->client = $client;
        $this->user = $user;
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

    /**
     * @psalm-mutation-free
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): self
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
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @return Scope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param Scope[] $scopes
     */
    public function setScopes(array $scopes): self
    {
        $this->scopes = $scopes;

        return $this;
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

    public function getCodeChallenge(): ?string
    {
        return $this->authorizationRequest->getCodeChallenge();
    }

    public function getCodeChallengeMethod(): ?string
    {
        return $this->authorizationRequest->getCodeChallengeMethod();
    }
}
