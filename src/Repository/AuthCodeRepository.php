<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Repository;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Entity\AuthCode;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @var AuthorizationCodeManagerInterface
     */
    private $authorizationCodeManager;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    public function __construct(
        AuthorizationCodeManagerInterface $authorizationCodeManager,
        ClientManagerInterface $clientManager,
        ScopeConverterInterface $scopeConverter
    ) {
        $this->authorizationCodeManager = $authorizationCodeManager;
        $this->clientManager = $clientManager;
        $this->scopeConverter = $scopeConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode(): AuthCode
    {
        return new AuthCode();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $authorizationCode = $this->authorizationCodeManager->find($authCodeEntity->getIdentifier());

        if (null !== $authorizationCode) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $authorizationCode = $this->buildAuthorizationCode($authCodeEntity);

        $this->authorizationCodeManager->save($authorizationCode);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId): void
    {
        $authorizationCode = $this->authorizationCodeManager->find($codeId);

        if (null === $authorizationCode) {
            return;
        }

        $authorizationCode->revoke();

        $this->authorizationCodeManager->save($authorizationCode);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId): bool
    {
        $authorizationCode = $this->authorizationCodeManager->find($codeId);

        if (null === $authorizationCode) {
            return true;
        }

        return $authorizationCode->isRevoked();
    }

    private function buildAuthorizationCode(AuthCodeEntityInterface $authCodeEntity): AuthorizationCode
    {
        /** @var AbstractClient $client */
        $client = $this->clientManager->find($authCodeEntity->getClient()->getIdentifier());

        $userIdentifier = $authCodeEntity->getUserIdentifier();
        if (null !== $userIdentifier) {
            $userIdentifier = (string) $userIdentifier;
        }

        return new AuthorizationCode(
            $authCodeEntity->getIdentifier(),
            $authCodeEntity->getExpiryDateTime(),
            $client,
            $userIdentifier,
            $this->scopeConverter->toDomainArray(array_values($authCodeEntity->getScopes()))
        );
    }
}
