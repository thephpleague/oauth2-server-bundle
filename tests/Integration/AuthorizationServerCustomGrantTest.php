<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantNullAccessTokenTTL;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantUndefinedAccessTokenTTL;
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

        $this->assertGrantConfig('fake_grant', new \DateInterval('PT3H'), $enabledGrantTypes, $grantTypeAccessTokenTTL, FakeGrant::class);
        $this->assertGrantConfig(FakeGrantNullAccessTokenTTL::class, new \DateInterval('PT1H'), $enabledGrantTypes, $grantTypeAccessTokenTTL);
        $this->assertGrantConfig(FakeGrantUndefinedAccessTokenTTL::class, new \DateInterval('PT2H'), $enabledGrantTypes, $grantTypeAccessTokenTTL);

        // TODO remove code bloc when bundle interface and configurator will be deleted
        $this->assertGrantConfig('fake_legacy_grant', new \DateInterval('PT5H'), $enabledGrantTypes, $grantTypeAccessTokenTTL, FakeLegacyGrant::class);
    }

    private function assertGrantConfig(string $grantId, ?\DateInterval $accessTokenTTL, array $enabledGrantTypes, array $grantTypeAccessTokenTTL, ?string $grantClass = null): void
    {
        $grantClass ??= $grantId;

        $this->assertArrayHasKey($grantId, $enabledGrantTypes);
        $this->assertInstanceOf($grantClass, $enabledGrantTypes[$grantId]);
        $this->assertArrayHasKey($grantId, $grantTypeAccessTokenTTL);
        $this->assertEquals($accessTokenTTL, $grantTypeAccessTokenTTL[$grantId]);
    }
}
