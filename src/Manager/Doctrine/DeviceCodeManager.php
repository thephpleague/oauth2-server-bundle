<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;
use Symfony\Component\Clock\ClockAwareTrait;

final readonly class DeviceCodeManager implements DeviceCodeManagerInterface
{
    use ClockAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function find(string $identifier): ?DeviceCodeInterface
    {
        return $this->entityManager->find(DeviceCode::class, $identifier);
    }

    public function findByUserCode(string $code): ?DeviceCodeInterface
    {
        /** @var ?DeviceCodeInterface */
        return $this->entityManager->createQueryBuilder()
           ->select('dc')
           ->from(DeviceCode::class, 'dc')
           ->where('dc.userCode = :code')
           ->setParameter('code', $code)
           ->getQuery()
           ->getOneOrNullResult();
    }

    public function save(DeviceCodeInterface $deviceCode): void
    {
        $this->entityManager->persist($deviceCode);
        $this->entityManager->flush();
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        $expiry = $this->now();
        if ($expireCleanupDelay) {
            $expiry = $expiry->sub($expireCleanupDelay);
        }

        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(DeviceCode::class, 'at')
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
