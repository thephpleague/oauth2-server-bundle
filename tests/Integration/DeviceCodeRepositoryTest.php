<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverter;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\Repository\DeviceCodeRepository;

final class DeviceCodeRepositoryTest extends AbstractIntegrationTest
{
    public function testDeviceCodeRevoking(): void
    {
        $identifier = 'foo';

        $deviceCode = new DeviceCode(
            $identifier,
            new \DateTimeImmutable('+1 day'),
            new Client('name', 'identifier', 'secret'),
            null,
            [],
            '',
            false,
            '',
            null,
            5
        );

        $this->deviceCodeManager->save($deviceCode);

        $this->assertSame($deviceCode, $this->deviceCodeManager->find($identifier));

        $deviceCodeRepository = new DeviceCodeRepository(
            $this->deviceCodeManager, $this->clientManager, new ScopeConverter(), new ClientRepository($this->clientManager)
        );

        $deviceCodeRepository->revokeDeviceCode($identifier);

        $this->assertTrue($deviceCode->isRevoked());
        $this->assertSame($deviceCode, $this->deviceCodeManager->find($identifier));
    }
}
