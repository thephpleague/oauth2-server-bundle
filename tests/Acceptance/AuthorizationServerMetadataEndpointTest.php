<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationServerMetadataEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AuthorizationServerMetadataEndpointTest extends AbstractAcceptanceTestCase
{
    public function testTheDocumentIsServedAtThePathOfTheIssuer(): void
    {
        $this->client->request('GET', '/.well-known/oauth-authorization-server/auth');

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $metadata = json_decode($response->getContent(), true);

        $this->assertSame('https://example.com/auth', $metadata['issuer']);
        $this->assertSame('https://example.com/authorize', $metadata['authorization_endpoint']);
        $this->assertSame('https://example.com/token', $metadata['token_endpoint']);
        $this->assertSame('https://example.com/device-code', $metadata['device_authorization_endpoint']);
    }

    public function testTheDocumentDescribesTheEnabledGrants(): void
    {
        $this->client->request('GET', '/.well-known/oauth-authorization-server/auth');

        $metadata = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEqualsCanonicalizing(['code', 'token'], $metadata['response_types_supported']);
        $this->assertEqualsCanonicalizing(
            ['authorization_code', 'client_credentials', 'implicit', 'password', 'refresh_token', 'urn:ietf:params:oauth:grant-type:device_code'],
            $metadata['grant_types_supported'],
        );
        $this->assertSame(['S256'], $metadata['code_challenge_methods_supported']);
        $this->assertSame(['client_secret_basic', 'client_secret_post', 'none'], $metadata['token_endpoint_auth_methods_supported']);
        $this->assertSame([FixtureFactory::FIXTURE_SCOPE_FIRST, FixtureFactory::FIXTURE_SCOPE_SECOND], $metadata['scopes_supported']);
    }

    public function testAListenerCanChangeTheDocument(): void
    {
        $this->client->getContainer()->get(EventDispatcherInterface::class)->addListener(
            OAuth2Events::AUTHORIZATION_SERVER_METADATA,
            static function (AuthorizationServerMetadataEvent $event): void {
                $event->setMetadata($event->getMetadata() + ['service_documentation' => 'https://example.com/docs']);
            },
        );

        $this->client->request('GET', '/.well-known/oauth-authorization-server/auth');

        $metadata = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://example.com/docs', $metadata['service_documentation']);
    }

    public function testThePathOfTheIssuerIsPartOfTheRoute(): void
    {
        $this->client->request('GET', '/.well-known/oauth-authorization-server');

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
