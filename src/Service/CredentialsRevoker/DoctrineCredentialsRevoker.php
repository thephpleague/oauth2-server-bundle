<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class DoctrineCredentialsRevoker implements CredentialsRevokerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(EntityManagerInterface $entityManager, ClientManagerInterface $clientManager)
    {
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
    }

    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = $user->getUserIdentifier();

        $this->entityManager->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revoked', ':revoked')
            ->where('at.userIdentifier = :userIdentifier')
            ->setParameter('revoked', true)
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->update(RefreshToken::class, 'rt')
            ->set('rt.revoked', ':revoked')
            ->where($queryBuilder->expr()->in(
                'rt.accessToken',
                $this->entityManager->createQueryBuilder()
                    ->select('at.identifier')
                    ->from(AccessToken::class, 'at')
                    ->where('at.userIdentifier = :userIdentifier')
                    ->getDQL()
            ))
            ->setParameter('revoked', true)
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(AuthorizationCode::class, 'ac')
            ->set('ac.revoked', ':revoked')
            ->where('ac.userIdentifier = :userIdentifier')
            ->setParameter('revoked', true)
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(DeviceCode::class, 'dc')
            ->set('dc.revoked', ':revoked')
            ->where('dc.userIdentifier = :userIdentifier')
            ->setParameter('revoked', true)
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();
    }

    public function revokeCredentialsForClient(AbstractClient $client): void
    {
        /** @var AbstractClient $doctrineClient */
        $doctrineClient = $this->clientManager->find($client->getIdentifier());

        $this->entityManager->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revoked', ':revoked')
            ->where('at.client = :client')
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->setParameter('revoked', true)
            ->getQuery()
            ->execute();

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update(RefreshToken::class, 'rt')
            ->set('rt.revoked', ':revoked')
            ->where($queryBuilder->expr()->in(
                'rt.accessToken',
                $this->entityManager->createQueryBuilder()
                    ->select('at.identifier')
                    ->from(AccessToken::class, 'at')
                    ->where('at.client = :client')
                    ->getDQL()
            ))
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->setParameter('revoked', true)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(AuthorizationCode::class, 'ac')
            ->set('ac.revoked', ':revoked')
            ->where('ac.client = :client')
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->setParameter('revoked', true)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(DeviceCode::class, 'dc')
            ->set('dc.revoked', ':revoked')
            ->where('dc.client = :client')
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->setParameter('revoked', true)
            ->getQuery()
            ->execute();
    }
}
