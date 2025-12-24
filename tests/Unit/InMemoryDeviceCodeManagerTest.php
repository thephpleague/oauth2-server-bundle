<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\DeviceCodeManager as InMemoryDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use PHPUnit\Framework\TestCase;

/**
 * @group time-sensitive
 */
final class InMemoryDeviceCodeManagerTest extends TestCase
{
    public function testClearExpired(): void
    {
        $inMemoryDeviceCodeManager = new InMemoryDeviceCodeManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryDeviceCodeManager->save($token);
        }

        $this->assertSame(3, $inMemoryDeviceCodeManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryDeviceCodeManager::class, 'deviceCodes');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryDeviceCodeManager));
    }

    private function buildClearExpiredTestData(): array
    {
        $validDeviceCodes = [
            '1111' => $this->buildDeviceCode('1111', '+1 day'),
            '2222' => $this->buildDeviceCode('2222', '+1 hour'),
            '3333' => $this->buildDeviceCode('3333', '+5 seconds'),
        ];

        $expiredDeviceCodes = [
            '5555' => $this->buildDeviceCode('5555', '-1 day'),
            '6666' => $this->buildDeviceCode('6666', '-1 hour'),
            '7777' => $this->buildDeviceCode('7777', '-1 second'),
        ];

        return [
            'input' => $validDeviceCodes + $expiredDeviceCodes,
            'output' => $validDeviceCodes,
        ];
    }

    private function buildDeviceCode(string $identifier, string $modify): DeviceCode
    {
        return new DeviceCode(
            $identifier,
            new \DateTimeImmutable($modify),
            new Client('name', 'identifier', 'secret'),
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
