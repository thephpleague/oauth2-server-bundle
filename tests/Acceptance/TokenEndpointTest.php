<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Event\TokenRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;

final class TokenEndpointTest extends AbstractAcceptanceTest
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

    public function testSuccessfulClientCredentialsRequest(): void
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $wasRequestAccessTokenEventDispatched = false;
        $accessToken = null;

        $eventDispatcher->addListener(RequestEvent::ACCESS_TOKEN_ISSUED, static function (RequestAccessTokenEvent $event) use (&$wasRequestAccessTokenEventDispatched, &$accessToken): void {
            $wasRequestAccessTokenEventDispatched = true;
            $accessToken = $event->getAccessToken();
        });

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'client_credentials',
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertArrayNotHasKey('refresh_token', $jsonResponse);
        $this->assertSame('bar', $response->headers->get('foo'));

        $this->assertTrue($wasRequestAccessTokenEventDispatched);

        $this->assertSame('foo', $accessToken->getClient()->getIdentifier());
        $this->assertNull($accessToken->getUserIdentifier());
    }

    public function testSuccessfulPasswordRequest(): void
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::USER_RESOLVE, static function (UserResolveEvent $event): void {
            $event->setUser(FixtureFactory::createUser());
        });

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $wasRequestAccessTokenEventDispatched = false;
        $wasRequestRefreshTokenEventDispatched = false;
        $accessToken = null;
        $refreshToken = null;

        $eventDispatcher->addListener(RequestEvent::ACCESS_TOKEN_ISSUED, static function (RequestAccessTokenEvent $event) use (&$wasRequestAccessTokenEventDispatched, &$accessToken): void {
            $wasRequestAccessTokenEventDispatched = true;
            $accessToken = $event->getAccessToken();
        });

        $eventDispatcher->addListener(RequestEvent::REFRESH_TOKEN_ISSUED, static function (RequestRefreshTokenEvent $event) use (&$wasRequestRefreshTokenEventDispatched, &$refreshToken): void {
            $wasRequestRefreshTokenEventDispatched = true;
            $refreshToken = $event->getRefreshToken();
        });

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertNotEmpty($jsonResponse['refresh_token']);
        $this->assertSame($response->headers->get('foo'), 'bar');

        $this->assertTrue($wasRequestAccessTokenEventDispatched);
        $this->assertTrue($wasRequestRefreshTokenEventDispatched);

        $this->assertSame('foo', $accessToken->getClient()->getIdentifier());
        $this->assertSame('user', $accessToken->getUserIdentifier());
        $this->assertSame($accessToken->getIdentifier(), $refreshToken->getAccessToken()->getIdentifier());
    }

    public function testSuccessfulRefreshTokenRequest(): void
    {
        $refreshToken = $this->client
            ->getContainer()
            ->get(RefreshTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_REFRESH_TOKEN);

        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            if ('bar' === $event->getResponse()->headers->get('foo')) {
                $newResponse = clone $event->getResponse();
                $newResponse->headers->remove('foo');
                $newResponse->headers->set('baz', 'qux');
                $event->setResponse($newResponse);
            }
        }, -1);

        $wasRequestAccessTokenEventDispatched = false;
        $wasRequestRefreshTokenEventDispatched = false;
        $accessToken = null;
        $refreshTokenEntity = null;

        $eventDispatcher->addListener(RequestEvent::ACCESS_TOKEN_ISSUED, static function (RequestAccessTokenEvent $event) use (&$wasRequestAccessTokenEventDispatched, &$accessToken): void {
            $wasRequestAccessTokenEventDispatched = true;
            $accessToken = $event->getAccessToken();
        });

        $eventDispatcher->addListener(RequestEvent::REFRESH_TOKEN_ISSUED, static function (RequestRefreshTokenEvent $event) use (&$wasRequestRefreshTokenEventDispatched, &$refreshTokenEntity): void {
            $wasRequestRefreshTokenEventDispatched = true;
            $refreshTokenEntity = $event->getRefreshToken();
        });

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'refresh_token',
            'refresh_token' => TestHelper::generateEncryptedPayload($refreshToken),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertNotEmpty($jsonResponse['refresh_token']);
        $this->assertFalse($response->headers->has('foo'));
        $this->assertSame($response->headers->get('baz'), 'qux');

        $this->assertTrue($wasRequestAccessTokenEventDispatched);
        $this->assertTrue($wasRequestRefreshTokenEventDispatched);

        $this->assertSame($refreshToken->getAccessToken()->getClient()->getIdentifier(), $accessToken->getClient()->getIdentifier());
        $this->assertSame($accessToken->getIdentifier(), $refreshTokenEntity->getAccessToken()->getIdentifier());
    }

    public function testSuccessfulAuthorizationCodeRequest(): void
    {
        $authCode = $this->client
            ->getContainer()
            ->get(AuthorizationCodeManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_AUTH_CODE);

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://example.org/oauth2/redirect-uri',
            'code' => TestHelper::generateEncryptedAuthCodePayload($authCode),
        ]);

        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
                $event->getResponse()->headers->set('foo', 'bar');
            });

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertEmpty($response->headers->get('foo'), 'bar');
    }

    public function testSuccessfulAuthorizationCodeRequestWithPublicClient(): void
    {
        $authCode = $this->client
            ->getContainer()
            ->get(AuthorizationCodeManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_AUTH_CODE_PUBLIC_CLIENT);

        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $wasRequestAccessTokenEventDispatched = false;
        $wasRequestRefreshTokenEventDispatched = false;
        $accessToken = null;
        $refreshToken = null;

        $eventDispatcher->addListener(RequestEvent::ACCESS_TOKEN_ISSUED, static function (RequestAccessTokenEvent $event) use (&$wasRequestAccessTokenEventDispatched, &$accessToken): void {
            $wasRequestAccessTokenEventDispatched = true;
            $accessToken = $event->getAccessToken();
        });

        $eventDispatcher->addListener(RequestEvent::REFRESH_TOKEN_ISSUED, static function (RequestRefreshTokenEvent $event) use (&$wasRequestRefreshTokenEventDispatched, &$refreshToken): void {
            $wasRequestRefreshTokenEventDispatched = true;
            $refreshToken = $event->getRefreshToken();
        });

        $this->client->request('POST', '/token', [
            'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
            'grant_type' => 'authorization_code',
            'redirect_uri' => FixtureFactory::FIXTURE_PUBLIC_CLIENT_REDIRECT_URI,
            'code' => TestHelper::generateEncryptedAuthCodePayload($authCode),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertNotEmpty($jsonResponse['refresh_token']);
        $this->assertSame($response->headers->get('foo'), 'bar');

        $this->assertTrue($wasRequestAccessTokenEventDispatched);
        $this->assertTrue($wasRequestRefreshTokenEventDispatched);

        $this->assertSame($authCode->getClient()->getIdentifier(), $accessToken->getClient()->getIdentifier());
        $this->assertSame($authCode->getUserIdentifier(), $accessToken->getUserIdentifier());
        $this->assertSame($accessToken->getIdentifier(), $refreshToken->getAccessToken()->getIdentifier());
    }

    public function testFailedTokenRequest(): void
    {
        $this->client->request('POST', '/token');

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('unsupported_grant_type', $jsonResponse['error']);
        $this->assertSame('The authorization grant type is not supported by the authorization server.', $jsonResponse['error_description']);
        $this->assertSame('Check that all required parameters have been provided', $jsonResponse['hint']);
    }

    public function testFailedClientCredentialsTokenRequest(): void
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $wasClientAuthenticationEventDispatched = false;

        $eventDispatcher->addListener(RequestEvent::CLIENT_AUTHENTICATION_FAILED, static function (RequestEvent $event) use (&$wasClientAuthenticationEventDispatched, &$accessToken): void {
            $wasClientAuthenticationEventDispatched = true;
        });

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'wrong',
            'grant_type' => 'client_credentials',
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('invalid_client', $jsonResponse['error']);
        $this->assertSame('Client authentication failed', $jsonResponse['error_description']);
        $this->assertSame('bar', $response->headers->get('foo'));

        $this->assertTrue($wasClientAuthenticationEventDispatched);
    }

    public function testSuccessfulDeviceCodeRequestWithPublicClient(): void
    {
        $deviceCode = $this->client
            ->getContainer()
            ->get(DeviceCodeManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_DEVICE_CODE_APPROVED);

        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(OAuth2Events::TOKEN_REQUEST_RESOLVE, static function (TokenRequestResolveEvent $event): void {
            $event->getResponse()->headers->set('foo', 'bar');
        });

        $wasRequestAccessTokenEventDispatched = false;
        $wasRequestRefreshTokenEventDispatched = false;
        $accessToken = null;
        $refreshToken = null;

        $eventDispatcher->addListener(RequestEvent::ACCESS_TOKEN_ISSUED, static function (RequestAccessTokenEvent $event) use (&$wasRequestAccessTokenEventDispatched, &$accessToken): void {
            $wasRequestAccessTokenEventDispatched = true;
            $accessToken = $event->getAccessToken();
        });

        $eventDispatcher->addListener(RequestEvent::REFRESH_TOKEN_ISSUED, static function (RequestRefreshTokenEvent $event) use (&$wasRequestRefreshTokenEventDispatched, &$refreshToken): void {
            $wasRequestRefreshTokenEventDispatched = true;
            $refreshToken = $event->getRefreshToken();
        });

        $this->client->request('POST', '/token', [
            'client_id' => $deviceCode->getClient()->getIdentifier(),
            'client_secret' => 'top_secret',
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'device_code' => $deviceCode->getIdentifier(),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('Bearer', $jsonResponse['token_type']);
        $this->assertLessThanOrEqual(3600, $jsonResponse['expires_in']);
        $this->assertGreaterThan(0, $jsonResponse['expires_in']);
        $this->assertNotEmpty($jsonResponse['access_token']);
        $this->assertNotEmpty($jsonResponse['refresh_token']);

        $this->assertTrue($wasRequestAccessTokenEventDispatched);
        $this->assertTrue($wasRequestRefreshTokenEventDispatched);

        $this->assertSame($deviceCode->getClient()->getIdentifier(), $accessToken->getClient()->getIdentifier());
        $this->assertSame($deviceCode->getUserIdentifier(), $accessToken->getUserIdentifier());
        $this->assertSame($accessToken->getIdentifier(), $refreshToken->getAccessToken()->getIdentifier());
    }

    public function testFailedDeviceCodeRequestWithExpiredCode(): void
    {
        $deviceCode = $this->client
            ->getContainer()
            ->get(DeviceCodeManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_DEVICE_CODE_EXPIRED);

        $this->client->request('POST', '/token', [
            'client_id' => $deviceCode->getClient()->getIdentifier(),
            'client_secret' => 'secret',
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'device_code' => $deviceCode->getIdentifier(),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('expired_token', $jsonResponse['error']);
        $this->assertSame('device_code', $jsonResponse['hint']);
    }

    public function testFailedDeviceCodeWithPendingCode(): void
    {
        $deviceCode = $this->client
            ->getContainer()
            ->get(DeviceCodeManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_DEVICE_CODE_PUBLIC_CLIENT);

        $this->client->request('POST', '/token', [
            'client_id' => $deviceCode->getClient()->getIdentifier(),
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'device_code' => $deviceCode->getIdentifier(),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('authorization_pending', $jsonResponse['error']);
        $this->assertSame('', $jsonResponse['hint']);
    }
}
