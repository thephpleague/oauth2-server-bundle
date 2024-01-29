<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->objectManager->find(AuthorizationCode::class, $identifier);
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->objectManager->persist($authCode);
        $this->objectManager->flush();
    }

    public function clearExpired(): int
    {
        /** @var int */
        return $this->objectManager->createQueryBuilder()
            ->delete(AuthorizationCode::class, 'ac')
            ->where('ac.expiry < :expiry')
            ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
            ->getQuery()
            ->execute();
    }
}
