<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /** @var bool */
    private $persistAccessToken;

    public function __construct(EntityManagerInterface $entityManager, bool $persistAccessToken)
    {
        $this->entityManager = $entityManager;
        $this->persistAccessToken = $persistAccessToken;
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

    public function clearExpired(): int
    {
        if (!$this->persistAccessToken) {
            return 0;
        }

        /** @var array{identifier: string}[] */
        $results = $this->entityManager->createQueryBuilder()
            ->select('at.identifier')
            ->from(AccessToken::class, 'at')
            ->where('at.expiry < :expiry')
            ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
            ->getQuery()
            ->getScalarResult();
        if (0 === \count($results)) {
            return 0;
        }

        /** @var string[] */
        $ids = array_column($results, 'identifier');

        // unlink access tokens from refresh tokens
        $this->entityManager->createQueryBuilder()
            ->update(RefreshToken::class, 'rt')
            ->set('rt.accessToken', ':null')
            ->where('rt.accessToken IN (:ids)')
            ->setParameter('null', null)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();

        // delete expired access tokens
        $this->entityManager->createQueryBuilder()
            ->delete(AccessToken::class, 'at')
            ->where('at.identifier IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();

        return \count($ids);
    }
}
