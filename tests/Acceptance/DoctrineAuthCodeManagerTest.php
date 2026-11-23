<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager as DoctrineAuthCodeManager;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * @TODO This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 */
#[CoversClass(DoctrineAuthCodeManager::class)]
final class DoctrineAuthCodeManagerTest extends AbstractAcceptanceTestCase
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

        $doctrineAuthCodeManager = new DoctrineAuthCodeManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AuthorizationCode $authCode */
        foreach ($testData['input'] as $authCode) {
            $doctrineAuthCodeManager->save($authCode);
        }

        $em->flush();

        $this->assertSame(3, $doctrineAuthCodeManager->clearExpiredTokens());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(AuthorizationCode::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    public function testClearExpiredTokensWithCleanupDelay(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineAuthCodeManager = new DoctrineAuthCodeManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AuthorizationCode $authCode */
        foreach ($testData['input'] as $authCode) {
            $doctrineAuthCodeManager->save($authCode);
        }

        $em->flush();

        $this->assertSame(2, $doctrineAuthCodeManager->clearExpiredTokens(new \DateInterval('PT30M')));
    }

    #[IgnoreDeprecations]
    public function testLegacyClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineAuthCodeManager = new DoctrineAuthCodeManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AuthorizationCode $authCode */
        foreach ($testData['input'] as $authCode) {
            $doctrineAuthCodeManager->save($authCode);
        }

        $em->flush();

        $this->expectUserDeprecationMessage('Since league/oauth2-server-bundle 2.1: Method "League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.');
        $this->assertSame(3, $doctrineAuthCodeManager->clearExpired());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(AuthorizationCode::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    private function buildClearExpiredTestData(Client $client): array
    {
        $validAuthCodes = [
            $this->buildAuthCode('1111', '+1 day', $client),
            $this->buildAuthCode('2222', '+1 hour', $client),
            $this->buildAuthCode('3333', '+5 second', $client),
        ];

        $expiredAuthCodes = [
            $this->buildAuthCode('5555', '-1 day', $client),
            $this->buildAuthCode('6666', '-1 hour', $client),
            $this->buildAuthCode('7777', '-1 second', $client),
        ];

        return [
            'input' => array_merge($validAuthCodes, $expiredAuthCodes),
            'output' => $validAuthCodes,
        ];
    }

    private function buildAuthCode(string $identifier, string $modify, Client $client): AuthorizationCode
    {
        return new AuthorizationCode(
            $identifier,
            $this->clock->now()->modify($modify),
            $client,
            null,
            []
        );
    }
}
