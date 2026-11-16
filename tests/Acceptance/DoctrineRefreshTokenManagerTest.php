<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager as DoctrineRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * @TODO This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 */
#[CoversClass(DoctrineRefreshTokenManager::class)]
final class DoctrineRefreshTokenManagerTest extends AbstractAcceptanceTestCase
{
    use ClockSensitiveTrait;

    private ClockInterface $clock;

    #[Before]
    protected function setMockClock(): void
    {
        $this->clock = static::mockTime(true);
    }

    public function testClearExpiredTokens(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineRefreshTokenManager = new DoctrineRefreshTokenManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $em->persist($token->getAccessToken());
            $doctrineRefreshTokenManager->save($token);
        }

        $em->flush();

        $this->assertSame(3, $doctrineRefreshTokenManager->clearExpiredTokens());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(RefreshToken::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    public function testClearExpiredTokensWithCleanupDelay(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineRefreshTokenManager = new DoctrineRefreshTokenManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $em->persist($token->getAccessToken());
            $doctrineRefreshTokenManager->save($token);
        }

        $em->flush();

        $this->assertSame(2, $doctrineRefreshTokenManager->clearExpiredTokens(new \DateInterval('PT30M')));
    }

    #[IgnoreDeprecations]
    public function testLegacyClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineRefreshTokenManager = new DoctrineRefreshTokenManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $em->persist($token->getAccessToken());
            $doctrineRefreshTokenManager->save($token);
        }

        $em->flush();

        $this->expectUserDeprecationMessage('Since league/oauth2-server-bundle 2.1: Method "League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.');
        $this->assertSame(3, $doctrineRefreshTokenManager->clearExpired());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(RefreshToken::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    private function buildClearExpiredTestData(Client $client): array
    {
        $validRefreshTokens = [
            $this->buildRefreshToken('1111', '+1 day', $client),
            $this->buildRefreshToken('2222', '+1 hour', $client),
            $this->buildRefreshToken('3333', '+5 second', $client),
        ];

        $expiredRefreshTokens = [
            $this->buildRefreshToken('5555', '-1 day', $client),
            $this->buildRefreshToken('6666', '-1 hour', $client),
            $this->buildRefreshToken('7777', '-1 second', $client),
        ];

        return [
            'input' => array_merge($validRefreshTokens, $expiredRefreshTokens),
            'output' => $validRefreshTokens,
        ];
    }

    private function buildRefreshToken(string $identifier, string $modify, Client $client): RefreshToken
    {
        return new RefreshToken(
            $identifier,
            $this->clock->now()->modify($modify),
            new AccessToken(
                $identifier,
                $this->clock->now()->modify('+1 day'),
                $client,
                null,
                []
            )
        );
    }
}
