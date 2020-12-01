<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager as DoctrineRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

/**
 * @TODO   This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 * @covers \League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager
 */
final class DoctrineRefreshTokenManagerTest extends AbstractAcceptanceTest
{
    public function testClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineRefreshTokenManager = new DoctrineRefreshTokenManager($em);

        $client = new Client('client', 'secret');
        $em->persist($client);
        $em->flush();

        $testData = $this->buildClearExpiredTestData($client);

        /** @var RefreshToken $token */
        foreach ($testData['input'] as $token) {
            $em->persist($token->getAccessToken());
            $doctrineRefreshTokenManager->save($token);
        }

        $em->flush();

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
            new \DateTimeImmutable($modify),
            new AccessToken(
                $identifier,
                new \DateTimeImmutable('+1 day'),
                $client,
                null,
                []
            )
        );
    }
}
