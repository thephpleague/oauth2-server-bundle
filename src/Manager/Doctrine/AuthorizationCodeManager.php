<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $expireCleanupDelay,
    ) {
    }

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->entityManager->find(AuthorizationCode::class, $identifier);
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->entityManager->persist($authCode);
        $this->entityManager->flush();
    }

    public function clearExpired(): int
    {
        $expiry = (new \DateTimeImmutable())->sub(new \DateInterval($this->expireCleanupDelay));

        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(AuthorizationCode::class, 'ac')
            ->where('ac.expiry < :expiry')
            ->setParameter('expiry', $expiry, 'datetime_immutable')
            ->getQuery()
            ->execute();
    }
}
