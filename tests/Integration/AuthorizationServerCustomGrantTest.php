<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeLegacyGrant;
use League\OAuth2\Server\AuthorizationServer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthorizationServerCustomGrantTest extends KernelTestCase
{
    public function testAuthorizationServerHasOurCustomGrantEnabled(): void
    {
        static::bootKernel();

        /** @var AuthorizationServer $authorizationServer */
        $authorizationServer = self::getContainer()->get(AuthorizationServer::class);

        $reflectionClass = new \ReflectionClass(AuthorizationServer::class);
        $reflectionProperty = $reflectionClass->getProperty('enabledGrantTypes');
        $reflectionProperty->setAccessible(true);

        $reflectionTTLProperty = $reflectionClass->getProperty('grantTypeAccessTokenTTL');
        $reflectionTTLProperty->setAccessible(true);

        $enabledGrantTypes = $reflectionProperty->getValue($authorizationServer);
        $grantTypeAccessTokenTTL = $reflectionTTLProperty->getValue($authorizationServer);

        $this->assertArrayHasKey('fake_grant', $enabledGrantTypes);
        $this->assertInstanceOf(FakeGrant::class, $enabledGrantTypes['fake_grant']);
        $this->assertArrayHasKey('fake_grant', $grantTypeAccessTokenTTL);
        $this->assertEquals(new \DateInterval('PT5H'), $grantTypeAccessTokenTTL['fake_grant']);

        // TODO remove code bloc when bundle interface and configurator will be deleted
        $this->assertArrayHasKey('fake_legacy_grant', $enabledGrantTypes);
        $this->assertInstanceOf(FakeLegacyGrant::class, $enabledGrantTypes['fake_legacy_grant']);
        $this->assertEquals(new \DateInterval('PT5H'), $enabledGrantTypes['fake_legacy_grant']->getAccessTokenTTL());
    }
}
