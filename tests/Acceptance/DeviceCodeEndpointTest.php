<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;

final class DeviceCodeEndpointTest extends AbstractAcceptanceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        FixtureFactory::initializeFixtures(
            $this->client->getContainer()->get(ScopeManagerInterface::class),
            $this->client->getContainer()->get(ClientManagerInterface::class),
            $this->client->getContainer()->get(AccessTokenManagerInterface::class),
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class),
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class),
            $this->client->getContainer()->get(DeviceCodeManagerInterface::class)
        );
    }

    public function testSuccessfulCodeRequest(): void
    {
        $this->client->request('POST', '/device-code', [
            'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonResponse['device_code']);
        $this->assertNotEmpty($jsonResponse['user_code']);
        $this->assertSame('', $jsonResponse['verification_uri']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
    }

    public function testFailedWithUnkownClientRequest(): void
    {
        $this->client->request('POST', '/device-code', [
            'client_id' => 'unknown_client',
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonResponse['error']);
        $this->assertNotEmpty($jsonResponse['error_description']);
        $this->assertSame('invalid_client', $jsonResponse['error']);
    }
}
