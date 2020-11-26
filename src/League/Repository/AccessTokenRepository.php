<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\League\Repository;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\League\Entity\AccessToken as AccessTokenEntity;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken as AccessTokenModel;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AccessTokenManagerInterface
     */
    private $accessTokenManager;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    public function __construct(
        AccessTokenManagerInterface $accessTokenManager,
        ClientManagerInterface $clientManager,
        ScopeConverterInterface $scopeConverter
    ) {
        $this->accessTokenManager = $accessTokenManager;
        $this->clientManager = $clientManager;
        $this->scopeConverter = $scopeConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $accessToken = $this->accessTokenManager->find($accessTokenEntity->getIdentifier());

        if (null !== $accessToken) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $accessToken = $this->buildAccessTokenModel($accessTokenEntity);

        $this->accessTokenManager->save($accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        $accessToken = $this->accessTokenManager->find($tokenId);

        if (null === $accessToken) {
            return;
        }

        $accessToken->revoke();

        $this->accessTokenManager->save($accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $accessToken = $this->accessTokenManager->find($tokenId);

        if (null === $accessToken) {
            return true;
        }

        return $accessToken->isRevoked();
    }

    private function buildAccessTokenModel(AccessTokenEntityInterface $accessTokenEntity): AccessTokenModel
    {
        $client = $this->clientManager->find($accessTokenEntity->getClient()->getIdentifier());

        return new AccessTokenModel(
            $accessTokenEntity->getIdentifier(),
            $accessTokenEntity->getExpiryDateTime(),
            $client,
            $accessTokenEntity->getUserIdentifier(),
            $this->scopeConverter->toDomainArray($accessTokenEntity->getScopes())
        );
    }
}
