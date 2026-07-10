<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantConfigurator;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Grant\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Grant\FakeLegacyGrant;
use League\OAuth2\Server\AuthorizationServer;
use PHPUnit\Framework\TestCase;

final class GrantConfiguratorTest extends TestCase
{
    public function testLegacyGrantConfiguration(): void
    {
        $authorizationServer = $this->createMock(AuthorizationServer::class);
        $configurator = new GrantConfigurator([
            new FakeGrant(),
            new FakeLegacyGrant(),
        ]);

        $authorizationServer->expects($this->once())
            ->method('enableGrantType')
            ->with($this->isInstanceOf(FakeLegacyGrant::class), $this->isInstanceOf(\DateInterval::class)
            );

        $configurator($authorizationServer);
    }
}
