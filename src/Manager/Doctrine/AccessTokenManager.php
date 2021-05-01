<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /** @var bool */
    private $disableAccessTokenSaving;

    public function __construct(EntityManagerInterface $entityManager, bool $disableAccessTokenSaving)
    {
        $this->entityManager = $entityManager;
        $this->disableAccessTokenSaving = $disableAccessTokenSaving;
    }

    public function find(string $identifier): ?AccessToken
    {
        if ($this->disableAccessTokenSaving) {
            return null;
        }

        return $this->entityManager->find(AccessToken::class, $identifier);
    }

    public function save(AccessToken $accessToken): void
    {
        if ($this->disableAccessTokenSaving) {
            return;
        }

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();
    }

    public function clearExpired(): int
    {
        if ($this->disableAccessTokenSaving) {
            return 0;
        }

        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(AccessToken::class, 'at')
            ->where('at.expiry < :expiry')
            ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
            ->getQuery()
            ->execute();
    }
}
