<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Repository;

use League\Bundle\OAuth2ServerBundle\Entity\RefreshToken as RefreshTokenEntity;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken as RefreshTokenModel;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var RefreshTokenManagerInterface
     */
    private $refreshTokenManager;

    /**
     * @var AccessTokenManagerInterface
     */
    private $accessTokenManager;

    public function __construct(
        RefreshTokenManagerInterface $refreshTokenManager,
        AccessTokenManagerInterface $accessTokenManager
    ) {
        $this->refreshTokenManager = $refreshTokenManager;
        $this->accessTokenManager = $accessTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $refreshToken = $this->refreshTokenManager->find($refreshTokenEntity->getIdentifier());

        if (null !== $refreshToken) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $refreshToken = $this->buildRefreshTokenModel($refreshTokenEntity);

        $this->refreshTokenManager->save($refreshToken);
    }

    /**
     * @param string $tokenId
     */
    public function revokeRefreshToken($tokenId): void
    {
        $refreshToken = $this->refreshTokenManager->find($tokenId);

        if (null === $refreshToken) {
            return;
        }

        $refreshToken->revoke();

        $this->refreshTokenManager->save($refreshToken);
    }

    /**
     * @param string $tokenId
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        $refreshToken = $this->refreshTokenManager->find($tokenId);

        if (null === $refreshToken) {
            return true;
        }

        return $refreshToken->isRevoked();
    }

    private function buildRefreshTokenModel(RefreshTokenEntityInterface $refreshTokenEntity): RefreshTokenModel
    {
        $accessToken = $this->accessTokenManager->find($refreshTokenEntity->getAccessToken()->getIdentifier());

        return new RefreshTokenModel(
            $refreshTokenEntity->getIdentifier(),
            $refreshTokenEntity->getExpiryDateTime(),
            $accessToken
        );
    }
}
