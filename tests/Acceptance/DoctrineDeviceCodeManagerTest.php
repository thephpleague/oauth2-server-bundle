<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\DeviceCodeManager as DoctrineDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;

/**
 * @TODO This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 *
 * @covers \League\Bundle\OAuth2ServerBundle\Manager\Doctrine\DeviceCodeManager
 */
final class DoctrineDeviceCodeManagerTest extends AbstractAcceptanceTest
{
    public function testClearExpired(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $doctrineDeviceCodeManager = new DoctrineDeviceCodeManager($em);

        $client = new Client('client', 'client', 'secret');
        $em->persist($client);

        $testData = $this->buildClearExpiredTestData($client);

        /** @var DeviceCode $authCode */
        foreach ($testData['input'] as $authCode) {
            $doctrineDeviceCodeManager->save($authCode);
        }

        $em->flush();

        $this->assertSame(3, $doctrineDeviceCodeManager->clearExpired());

        $this->assertSame(
            array_values($testData['output']),
            $em->getRepository(DeviceCode::class)->findBy([], ['identifier' => 'ASC'])
        );
    }

    private function buildClearExpiredTestData($client): array
    {
        $validDeviceCodes = [
            '1111' => $this->buildDeviceCode('1111', '+1 day', $client),
            '2222' => $this->buildDeviceCode('2222', '+1 hour', $client),
            '3333' => $this->buildDeviceCode('3333', '+5 seconds', $client),
        ];

        $expiredDeviceCodes = [
            '5555' => $this->buildDeviceCode('5555', '-1 day', $client),
            '6666' => $this->buildDeviceCode('6666', '-1 hour', $client),
            '7777' => $this->buildDeviceCode('7777', '-1 second', $client),
        ];

        return [
            'output' => $validDeviceCodes,
            'input' => $validDeviceCodes + $expiredDeviceCodes,
        ];
    }

    private function buildDeviceCode(string $identifier, string $modify, $client): DeviceCode
    {
        return new DeviceCode(
            $identifier,
            new \DateTimeImmutable($modify),
            $client,
            null,
            [],
            '',
            false,
            '',
            null,
            5
        );
    }
}
