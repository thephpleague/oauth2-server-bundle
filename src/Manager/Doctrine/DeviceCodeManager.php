<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

final class DeviceCodeManager implements DeviceCodeManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(string $identifier): ?DeviceCodeInterface
    {
        return $this->entityManager->find(DeviceCode::class, $identifier);
    }

    public function findByUserCode(string $code): ?DeviceCodeInterface
    {
        /** @var DeviceCodeInterface */
        return $this->entityManager->createQueryBuilder()
                                   ->select('dc')
                                   ->from(DeviceCode::class, 'dc')
                                   ->where('dc.userCode = :code')
                                   ->setParameter('code', $code)
                                   ->getQuery()
                                   ->getOneOrNullResult();
    }

    public function save(DeviceCodeInterface $deviceCode, bool $persist = true): void
    {
        if ($persist) {
            $this->entityManager->persist($deviceCode);
        }
        $this->entityManager->flush();
    }

    public function clearExpired(): int
    {
        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(DeviceCode::class, 'at')
            ->where('at.expiry < :expiry')
            ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
            ->getQuery()
            ->execute();
    }

}
