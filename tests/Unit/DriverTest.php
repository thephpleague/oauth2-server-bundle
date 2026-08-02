<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Driver;
use PHPUnit\Framework\TestCase;

final class DriverTest extends TestCase
{
    public function testDeviceCodeIsMappedWhenDeviceCodeGrantIsEnabled(): void
    {
        $driver = new Driver(Client::class, true, 'oauth2_', true);

        $this->assertContains(DeviceCode::class, $driver->getAllClassNames());
    }

    public function testDeviceCodeIsNotMappedWhenDeviceCodeGrantIsDisabled(): void
    {
        $driver = new Driver(Client::class, true, 'oauth2_', false);

        $this->assertNotContains(DeviceCode::class, $driver->getAllClassNames());
    }
}
