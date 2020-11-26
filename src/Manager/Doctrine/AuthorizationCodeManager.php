<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?AuthorizationCode
    {
        return $this->entityManager->find(AuthorizationCode::class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function save(AuthorizationCode $authorizationCode): void
    {
        $this->entityManager->persist($authorizationCode);
        $this->entityManager->flush();
    }

    public function clearExpired(): int
    {
        return $this->entityManager->createQueryBuilder()
            ->delete(AuthorizationCode::class, 'ac')
            ->where('ac.expiry < :expiry')
            ->setParameter('expiry', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
