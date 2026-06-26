<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantNullAccessTokenTTL;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantUndefinedAccessTokenTTL;
use League\OAuth2\Server\AuthorizationServer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthorizationServerCustomGrantTest extends KernelTestCase
{
    public function testAuthorizationServerHasOurCustomGrantEnabled(): void
    {
        static::bootKernel(['environment' => 'fake_grant']);

        /** @var AuthorizationServer $authorizationServer */
        $authorizationServer = self::getContainer()->get(AuthorizationServer::class);

        $reflectionClass = new \ReflectionClass(AuthorizationServer::class);
        $reflectionProperty = $reflectionClass->getProperty('enabledGrantTypes');

        $reflectionTTLProperty = $reflectionClass->getProperty('grantTypeAccessTokenTTL');

        $enabledGrantTypes = $reflectionProperty->getValue($authorizationServer);
        $grantTypeAccessTokenTTL = $reflectionTTLProperty->getValue($authorizationServer);

        $this->assertGrantConfig('fake_grant', new \DateInterval('PT3H'), $enabledGrantTypes, $grantTypeAccessTokenTTL, FakeGrant::class);
        $this->assertGrantConfig(FakeGrantNullAccessTokenTTL::class, new \DateInterval('PT1H'), $enabledGrantTypes, $grantTypeAccessTokenTTL);
        $this->assertGrantConfig(FakeGrantUndefinedAccessTokenTTL::class, new \DateInterval('PT2H'), $enabledGrantTypes, $grantTypeAccessTokenTTL);
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
