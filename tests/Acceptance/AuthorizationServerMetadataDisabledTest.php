<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Symfony\Bundle\FrameworkBundle\Console\Application;

final class AuthorizationServerMetadataDisabledTest extends AbstractAcceptanceTestCase
{
    protected function setUp(): void
    {
        $this->client = self::createClient(['environment' => 'metadata_disabled']);
        $this->application = new Application($this->client->getKernel());
    }

    public function testNothingIsRegisteredWhileTheMetadataAreOff(): void
    {
        $this->assertFalse($this->client->getContainer()->has('league.oauth2_server.controller.authorization_server_metadata'));
    }
}
