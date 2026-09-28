<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUserFactoryInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityLayerTest extends AbstractAcceptanceTestCase
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

    public function testAuthenticatedGuestRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_PUBLIC);

        $this->client->request('GET', '/security-test', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, foo', $response->getContent());
    }

    public function testCustomClientCredentialsUserFactoryIsUsedByFirewall(): void
    {
        $this->client->getContainer()->set('league.oauth2_server.client_credentials_user_factory', new class implements ClientCredentialsUserFactoryInterface {
            public function createUser(string $clientId): UserInterface
            {
                return FixtureFactory::createUser([], 'custom_' . $clientId);
            }
        });

        $accessTokenManager = $this->client->getContainer()->get(AccessTokenManagerInterface::class);
        $this->assertInstanceOf(AccessTokenManagerInterface::class, $accessTokenManager);
        $accessToken = $accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_PUBLIC);
        $this->assertInstanceOf(AccessToken::class, $accessToken);

        $this->client->request('GET', '/security-test', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Hello, custom_foo', $this->client->getResponse()->getContent());
    }

    public function testAuthenticatedGuestScopedRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_WITH_SCOPES);

        $this->client->request('GET', '/security-test-scopes', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Only certain scopes should be able to access this action.', $response->getContent());
    }

    public function testAuthenticatedUserRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND);

        $this->client->request('GET', '/security-test', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, user', $response->getContent());
    }

    public function testAuthenticatedUserRolesRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND_WITH_SCOPES);

        $this->client->request('GET', '/security-test-roles', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('These are the roles I have currently assigned: ROLE_OAUTH2_FANCY, ROLE_USER', $response->getContent());
    }

    public function testSuccessfulAuthorizationForAuthenticatedUserRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND_WITH_SCOPES);

        $this->client->request('GET', '/security-test-authorization', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('access granted', $response->getContent());
    }

    public function testUnsuccessfulAuthorizationForAuthenticatedUserRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND);

        $this->client->request('GET', '/security-test-authorization', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotSame('access granted', $response->getContent());
    }

    public function testExpiredRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED);

        $this->client->request('GET', '/security-test', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRevokedRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_REVOKED);

        $this->client->request('GET', '/security-test', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testInsufficientScopeRequest(): void
    {
        $accessToken = $this->client
            ->getContainer()
            ->get(AccessTokenManagerInterface::class)
            ->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_PUBLIC);

        $this->client->request('GET', '/security-test-scopes', [], [], [
            'HTTP_AUTHORIZATION' => \sprintf('Bearer %s', TestHelper::generateJwtToken($accessToken)),
        ]);

        $response = $this->client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testInvalidRequest(): void
    {
        $this->client->request('GET', '/security-test');

        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, guest', $response->getContent());
    }
}
