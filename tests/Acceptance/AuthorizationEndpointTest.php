<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizationEndpointTest extends AbstractAcceptanceTest
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

    private function loginUser(string $username = FixtureFactory::FIXTURE_USER, string $firewallContext = 'authorization'): void
    {
        $userProvider = static::getContainer()->get('security.user_providers');
        $user = $userProvider->loadUserByIdentifier($username);
        $this->client->loginUser($user, $firewallContext);
    }

    public function testSuccessfulCodeRequest(): void
    {
        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'code',
                'state' => 'foobar',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $query = [];
        parse_str(parse_url($redirectUri, \PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertEquals('foobar', $query['state']);
    }

    public function testSuccessfulPKCEAuthCodeRequest(): void
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(64));
        $codeChallengeMethod = 'S256';

        $codeChallenge = strtr(
            rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='),
            '+/',
            '-_'
        );

        $this->loginUser();

        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, function (AuthorizationRequestResolveEvent $event) use ($state, $codeChallenge, $codeChallengeMethod): void {
                $this->assertSame($state, $event->getState());
                $this->assertSame($codeChallenge, $event->getCodeChallenge());
                $this->assertSame($codeChallengeMethod, $event->getCodeChallengeMethod());

                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
                'response_type' => 'code',
                'scope' => '',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $query = [];
        parse_str(parse_url($redirectUri, \PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame($state, $query['state']);

        $this->assertArrayHasKey('code', $query);
        $payload = json_decode(TestHelper::decryptPayload($query['code']), true);

        $this->assertArrayHasKey('code_challenge', $payload);
        $this->assertArrayHasKey('code_challenge_method', $payload);
        $this->assertSame($codeChallenge, $payload['code_challenge']);
        $this->assertSame($codeChallengeMethod, $payload['code_challenge_method']);

        /** @var AuthorizationCode|null $authCode */
        $authCode = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(AuthorizationCode::class)
            ->findOneBy(['identifier' => $payload['auth_code_id']]);

        $this->assertInstanceOf(AuthorizationCode::class, $authCode);
        $this->assertSame(FixtureFactory::FIXTURE_PUBLIC_CLIENT, $authCode->getClient()->getIdentifier());
        $this->assertSame(FixtureFactory::FIXTURE_USER, $authCode->getUserIdentifier());
    }

    public function testSuccessfulAuthCodeRequestWhenTheLoggedUserIsOverriddenInTheAuthorizationRequestResolveEvent(): void
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(64));
        $codeChallengeMethod = 'S256';

        $codeChallenge = strtr(
            rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='),
            '+/',
            '-_'
        );

        $this->loginUser();

        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, function (AuthorizationRequestResolveEvent $event) use ($state, $codeChallenge, $codeChallengeMethod): void {
                $this->assertSame($state, $event->getState());
                $this->assertSame($codeChallenge, $event->getCodeChallenge());
                $this->assertSame($codeChallengeMethod, $event->getCodeChallengeMethod());

                $event->setUser(FixtureFactory::createUser([], FixtureFactory::FIXTURE_USER_TWO));
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
                'response_type' => 'code',
                'scope' => '',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $query = [];
        parse_str(parse_url($redirectUri, \PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame($state, $query['state']);

        $this->assertArrayHasKey('code', $query);
        $payload = json_decode(TestHelper::decryptPayload($query['code']), true);

        $this->assertArrayHasKey('code_challenge', $payload);
        $this->assertArrayHasKey('code_challenge_method', $payload);
        $this->assertSame($codeChallenge, $payload['code_challenge']);
        $this->assertSame($codeChallengeMethod, $payload['code_challenge_method']);

        /** @var AuthorizationCode|null $authCode */
        $authCode = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(AuthorizationCode::class)
            ->findOneBy(['identifier' => $payload['auth_code_id']]);

        $this->assertInstanceOf(AuthorizationCode::class, $authCode);
        $this->assertSame(FixtureFactory::FIXTURE_PUBLIC_CLIENT, $authCode->getClient()->getIdentifier());
        $this->assertSame(FixtureFactory::FIXTURE_USER_TWO, $authCode->getUserIdentifier());
    }

    public function testAuthCodeRequestWithPublicClientWithoutCodeChallengeWhenTheChallengeIsRequiredForPublicClients(): void
    {
        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, function (AuthorizationRequestResolveEvent $event): void {
                $this->fail('This event should not have been dispatched.');
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
                'response_type' => 'code',
                'scope' => '',
                'state' => bin2hex(random_bytes(20)),
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('invalid_request', $jsonResponse['error']);
        $this->assertSame('The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.', $jsonResponse['error_description']);
        $this->assertSame('Code challenge must be provided for public clients', $jsonResponse['hint']);
    }

    public function testAuthCodeRequestWithClientWhoIsNotAllowedToMakeARequestWithPlainCodeChallengeMethod(): void
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallengeMethod = 'plain';
        $codeChallenge = strtr(rtrim(base64_encode($codeVerifier), '='), '+/', '-_');

        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, function (AuthorizationRequestResolveEvent $event): void {
                $this->fail('This event should not have been dispatched.');
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT,
                'response_type' => 'code',
                'scope' => '',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('invalid_request', $jsonResponse['error']);
        $this->assertSame('The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.', $jsonResponse['error_description']);
        $this->assertSame('Plain code challenge method is not allowed for this client', $jsonResponse['hint']);
    }

    public function testAuthCodeRequestWithClientWhoIsAllowedToMakeARequestWithPlainCodeChallengeMethod(): void
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallengeMethod = 'plain';
        $codeChallenge = strtr(rtrim(base64_encode($codeVerifier), '='), '+/', '-_');

        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, function (AuthorizationRequestResolveEvent $event) use ($state, $codeChallenge, $codeChallengeMethod): void {
                $this->assertSame($state, $event->getState());
                $this->assertSame($codeChallenge, $event->getCodeChallenge());
                $this->assertSame($codeChallengeMethod, $event->getCodeChallengeMethod());

                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_PUBLIC_CLIENT_ALLOWED_TO_USE_PLAIN_CHALLENGE_METHOD,
                'response_type' => 'code',
                'scope' => '',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_PUBLIC_CLIENT_ALLOWED_TO_USE_PLAIN_CHALLENGE_METHOD_REDIRECT_URI, $redirectUri);
        $query = [];
        parse_str(parse_url($redirectUri, \PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame($state, $query['state']);

        $this->assertArrayHasKey('code', $query);
        $payload = json_decode(TestHelper::decryptPayload($query['code']), true);

        $this->assertArrayHasKey('code_challenge', $payload);
        $this->assertArrayHasKey('code_challenge_method', $payload);
        $this->assertSame($codeChallenge, $payload['code_challenge']);
        $this->assertSame($codeChallengeMethod, $payload['code_challenge_method']);

        /** @var AuthorizationCode|null $authCode */
        $authCode = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(AuthorizationCode::class)
            ->findOneBy(['identifier' => $payload['auth_code_id']]);

        $this->assertInstanceOf(AuthorizationCode::class, $authCode);
        $this->assertSame(FixtureFactory::FIXTURE_PUBLIC_CLIENT_ALLOWED_TO_USE_PLAIN_CHALLENGE_METHOD, $authCode->getClient()->getIdentifier());
    }

    public function testSuccessfulTokenRequest(): void
    {
        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'token',
                'state' => 'foobar',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $fragment = [];
        parse_str(parse_url($redirectUri, \PHP_URL_FRAGMENT), $fragment);
        $this->assertArrayHasKey('access_token', $fragment);
        $this->assertArrayHasKey('token_type', $fragment);
        $this->assertArrayHasKey('expires_in', $fragment);
        $this->assertArrayHasKey('state', $fragment);
        $this->assertEquals('foobar', $fragment['state']);
    }

    public function testCodeRequestRedirectToResolutionUri(): void
    {
        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
                $event->setResponse(new Response(null, 302, [
                    'Location' => '/authorize/consent',
                ]));
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'code',
                'state' => 'foobar',
                'redirect_uri' => FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI,
                'scope' => FixtureFactory::FIXTURE_SCOPE_FIRST . ' ' . FixtureFactory::FIXTURE_SCOPE_SECOND,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');
        $this->assertEquals('/authorize/consent', $redirectUri);
    }

    public function testAuthorizationRequestEventIsStoppedAfterSettingAResponse(): void
    {
        $eventDispatcher = $this->client
            ->getContainer()
            ->get('event_dispatcher');
        $eventDispatcher->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
        }, 100);
        $eventDispatcher->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
            $event->setResponse(new Response(null, 302, [
                'Location' => '/authorize/consent',
            ]));
        }, 200);

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'code',
                'state' => 'foobar',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');
        $this->assertEquals('/authorize/consent', $redirectUri);
    }

    public function testAuthorizationRequestEventIsStoppedAfterResolution(): void
    {
        $eventDispatcher = $this->client
            ->getContainer()
            ->get('event_dispatcher');
        $eventDispatcher->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
        }, 200);
        $eventDispatcher->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
            $event->setResponse(
                new Response(null, 302, [
                    'Location' => '/authorize/consent',
                ])
            );
        }, 100);

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'code',
                'state' => 'foobar',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $query = [];
        parse_str(parse_url($redirectUri, \PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertEquals('foobar', $query['state']);
    }

    public function testFailedCodeRequestRedirectWithFakedRedirectUri(): void
    {
        $this->client
            ->getContainer()
            ->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'code',
                'state' => 'foobar',
                'redirect_uri' => 'https://example.org/oauth2/malicious-uri',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('invalid_client', $jsonResponse['error']);
        $this->assertSame('Client authentication failed', $jsonResponse['error_description']);
    }

    public function testFailedAuthorizeRequest(): void
    {
        $this->loginUser();
        $this->client->request(
            'GET',
            '/authorize'
        );

        $response = $this->client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertSame('unsupported_grant_type', $jsonResponse['error']);
        $this->assertSame('The authorization grant type is not supported by the authorization server.', $jsonResponse['error_description']);
        $this->assertSame('Check that all required parameters have been provided', $jsonResponse['hint']);
    }

    public function testUnathorizedImplicitRequest(): void
    {
        $this->loginUser();

        $this->client->request(
            'GET',
            '/authorize',
            [
                'client_id' => FixtureFactory::FIXTURE_CLIENT_FIRST,
                'response_type' => 'token',
                'state' => 'foobar',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $redirectUri = $response->headers->get('Location');

        $this->assertStringStartsWith(FixtureFactory::FIXTURE_CLIENT_FIRST_REDIRECT_URI, $redirectUri);
        $fragment = [];
        parse_str(parse_url($redirectUri, \PHP_URL_FRAGMENT), $fragment);
        $this->assertArrayHasKey('error', $fragment);
        $this->assertArrayHasKey('error_description', $fragment);
        $this->assertArrayHasKey('state', $fragment);
        $this->assertEquals('access_denied', $fragment['error']);
        $this->assertEquals('foobar', $fragment['state']);
    }
}
