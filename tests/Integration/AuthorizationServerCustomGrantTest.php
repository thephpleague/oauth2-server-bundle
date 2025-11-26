<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
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

        $enabledGrantTypes = $reflectionProperty->getValue($authorizationServer);

        $this->assertArrayHasKey('fake_grant', $enabledGrantTypes);
        $this->assertInstanceOf(FakeGrant::class, $enabledGrantTypes['fake_grant']);
        $this->assertEquals(new \DateInterval('PT5H'), $enabledGrantTypes['fake_grant']->getAccessTokenTTL());
    }
}
