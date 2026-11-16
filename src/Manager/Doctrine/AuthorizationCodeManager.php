<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;
use Symfony\Component\Clock\ClockAwareTrait;

final readonly class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    use ClockAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
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

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        $expiry = $this->now();
        if ($expireCleanupDelay) {
            $expiry = $expiry->sub($expireCleanupDelay);
        }

        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(AuthorizationCode::class, 'ac')
            ->where('ac.expiry < :expiry')
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
