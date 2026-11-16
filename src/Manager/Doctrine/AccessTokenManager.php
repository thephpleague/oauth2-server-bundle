<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use Symfony\Component\Clock\ClockAwareTrait;

final readonly class AccessTokenManager implements AccessTokenManagerInterface
{
    use ClockAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private bool $persistAccessToken,
    ) {
    }

    public function find(string $identifier): ?AccessTokenInterface
    {
        if (!$this->persistAccessToken) {
            return null;
        }

        return $this->entityManager->find(AccessToken::class, $identifier);
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        if (!$this->persistAccessToken) {
            return;
        }

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        if (!$this->persistAccessToken) {
            return 0;
        }

        $expiry = $this->now();
        if ($expireCleanupDelay) {
            $expiry = $expiry->sub($expireCleanupDelay);
        }

        // unlink access tokens from refresh tokens
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->update(RefreshToken::class, 'rt')
            ->set('rt.accessToken', ':null')
            ->where($queryBuilder->expr()->in(
                'rt.accessToken',
                $this->entityManager->createQueryBuilder()
                    ->select('at.identifier')
                    ->from(AccessToken::class, 'at')
                    ->where('at.expiry < :expiry')
                    ->getDQL()
            ))
            ->setParameter('null', null)
            ->setParameter('expiry', $expiry, 'datetime_immutable')
            ->getQuery()
            ->execute();

        // delete expired access tokens
        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(AccessToken::class, 'at')
            ->where('at.expiry < :expiry')
            ->setParameter('expiry', $expiry, 'datetime_immutable')
            ->getQuery()
            ->execute();
    }

    public function clearExpired(): int
    {
        trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Method "%s::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.', self::class);

        return $this->clearExpiredTokens();
    }
}
