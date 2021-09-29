<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
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

    /**
     * @psalm-suppress DeprecatedMethod
     */
    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername();

        $this->entityManager->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revoked', true)
            ->where('at.userIdentifier = :userIdentifier')
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->update(RefreshToken::class, 'rt')
            ->set('rt.revoked', true)
            ->where($queryBuilder->expr()->in(
                'rt.accessToken',
                $this->entityManager->createQueryBuilder()
                    ->select('at.identifier')
                    ->from(AccessToken::class, 'at')
                    ->where('at.userIdentifier = :userIdentifier')
                    ->getDQL()
            ))
            ->setParameter('userIdentifier', $userIdentifier)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(AuthorizationCode::class, 'ac')
            ->set('ac.revoked', true)
            ->where('ac.userIdentifier = :userIdentifier')
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
            ->set('at.revoked', true)
            ->where('at.client = :client')
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->getQuery()
            ->execute();

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update(RefreshToken::class, 'rt')
            ->set('rt.revoked', true)
            ->where($queryBuilder->expr()->in(
                'rt.accessToken',
                $this->entityManager->createQueryBuilder()
                    ->select('at.identifier')
                    ->from(AccessToken::class, 'at')
                    ->where('at.client = :client')
                    ->getDQL()
            ))
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(AuthorizationCode::class, 'ac')
            ->set('ac.revoked', true)
            ->where('ac.client = :client')
            ->setParameter('client', $doctrineClient->getIdentifier(), 'string')
            ->getQuery()
            ->execute();
    }
}
