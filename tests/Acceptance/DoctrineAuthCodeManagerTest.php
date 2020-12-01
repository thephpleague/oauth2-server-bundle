<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager as DoctrineAuthCodeManager;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;

/**
 * @TODO   This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 * @covers \League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager
 */
final class DoctrineAuthCodeManagerTest extends AbstractAcceptanceTest
{
    public function testClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineAuthCodeManager = new DoctrineAuthCodeManager($em);

        $client = new Client('client', 'secret');
        $em->persist($client);

        $testData = $this->buildClearExpiredTestData($client);

        /** @var AuthorizationCode $authCode */
        foreach ($testData['input'] as $authCode) {
            $doctrineAuthCodeManager->save($authCode);
        }

        $em->flush();

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
            new \DateTimeImmutable($modify),
            $client,
            null,
            []
        );
    }
}
