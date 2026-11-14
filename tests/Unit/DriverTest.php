<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use Doctrine\ORM\Mapping\ClassMetadata;
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

    public function testTheClientIdentifierIs32CharactersLongByDefault(): void
    {
        $this->assertSame(32, $this->identifierLength(new Driver(Client::class, true, 'oauth2_', false)));
    }

    public function testTheClientIdentifierCanBeLong(): void
    {
        $this->assertSame(255, $this->identifierLength(new Driver(Client::class, true, 'oauth2_', false, true)));
    }

    private function identifierLength(Driver $driver): int
    {
        $metadata = new ClassMetadata(Client::class);
        $driver->loadMetadataForClass(Client::class, $metadata);

        return $metadata->getFieldMapping('identifier')['length'];
    }

    public function testDeviceCodeIsNotMappedWhenDeviceCodeGrantIsDisabled(): void
    {
        $driver = new Driver(Client::class, true, 'oauth2_', false);

        $this->assertNotContains(DeviceCode::class, $driver->getAllClassNames());
    }
}
