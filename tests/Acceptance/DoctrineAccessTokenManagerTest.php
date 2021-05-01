<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AccessTokenManager as DoctrineAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\Null\AccessTokenManager as NullAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

/**
 * @TODO   This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 * @covers \League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AccessTokenManager
 */
final class DoctrineAccessTokenManagerTest extends AbstractAcceptanceTest
{
    public function testClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineAccessTokenManager = new DoctrineAccessTokenManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AccessToken $token */
        foreach ($testData['input'] as $token) {
            $doctrineAccessTokenManager->save($token);
        }

        $this->assertSame(3, $doctrineAccessTokenManager->clearExpired());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(AccessToken::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    public function testClearExpiredWithoutSavingAccessToken(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $client = new Client('name', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $doctrineAccessTokenManager = new NullAccessTokenManager();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AccessToken $token */
        foreach ($testData['input'] as $token) {
            $doctrineAccessTokenManager->save($token);
        }

        $this->assertSame(0, $doctrineAccessTokenManager->clearExpired());

        $this->assertSame(
            [],
            $em->getRepository(AccessToken::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    private function buildClearExpiredTestData(Client $client): array
    {
        $validAccessTokens = [
            $this->buildAccessToken('1111', '+1 day', $client),
            $this->buildAccessToken('2222', '+1 hour', $client),
            $this->buildAccessToken('3333', '+1 second', $client),
            $this->buildAccessToken('4444', 'now', $client),
        ];

        $expiredAccessTokens = [
            $this->buildAccessToken('5555', '-1 day', $client),
            $this->buildAccessToken('6666', '-1 hour', $client),
            $this->buildAccessToken('7777', '-1 second', $client),
        ];

        return [
            'input' => array_merge($validAccessTokens, $expiredAccessTokens),
            'output' => $validAccessTokens,
        ];
    }

    private function buildAccessToken(string $identifier, string $modify, Client $client): AccessToken
    {
        return new AccessToken(
            $identifier,
            new \DateTimeImmutable($modify),
            $client,
            null,
            []
        );
    }

    public function testClearExpiredWithRefreshToken(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $doctrineAccessTokenManager = new DoctrineAccessTokenManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestDataWithRefreshToken($client);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $doctrineAccessTokenManager->save($token->getAccessToken());
            $em->persist($token);
        }

        $em->flush();

        $this->assertSame(3, $doctrineAccessTokenManager->clearExpired());

        $this->assertSame(
            $testData['output'],
            $em->getRepository(RefreshToken::class)->findBy(['accessToken' => null], ['identifier' => 'ASC'])
        );
    }

    public function testClearExpiredWithRefreshTokenWithoutSavingAccessToken(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $client = new Client('name', 'client', 'secret');
        $em->persist($client);
        $em->flush();

        $doctrineAccessTokenManager = new NullAccessTokenManager();

        $testData = $this->buildClearExpiredTestDataWithRefreshToken($client, false);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $em->persist($token);
        }

        $em->flush();

        $this->assertSame(0, $doctrineAccessTokenManager->clearExpired());

        $this->assertSame(
            $testData['input'],
            $em->getRepository(RefreshToken::class)->findBy(['accessToken' => null], ['identifier' => 'ASC'])
        );
    }

    private function buildClearExpiredTestDataWithRefreshToken(Client $client, bool $withAccessToken = true): array
    {
        $validRefreshTokens = [
            $this->buildRefreshToken('1111', '+1 day', $client, $withAccessToken),
            $this->buildRefreshToken('2222', '+1 hour', $client, $withAccessToken),
            $this->buildRefreshToken('3333', '+5 second', $client, $withAccessToken),
        ];

        $expiredRefreshTokens = [
            $this->buildRefreshToken('5555', '-1 day', $client, $withAccessToken),
            $this->buildRefreshToken('6666', '-1 hour', $client, $withAccessToken),
            $this->buildRefreshToken('7777', '-1 second', $client, $withAccessToken),
        ];

        return [
            'input' => array_merge($validRefreshTokens, $expiredRefreshTokens),
            'output' => $expiredRefreshTokens,
        ];
    }

    private function buildRefreshToken(string $identifier, string $modify, Client $client, bool $withAccessToken = true): RefreshToken
    {
        $accessToken = null;
        if ($withAccessToken) {
            $accessToken = new AccessToken(
                $identifier,
                new \DateTimeImmutable($modify),
                $client,
                null,
                []
            );
        }

        return new RefreshToken(
            $identifier,
            new \DateTimeImmutable('+1 day'),
            $accessToken
        );
    }
}
