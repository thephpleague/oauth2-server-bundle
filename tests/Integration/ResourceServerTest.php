<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;

final class ResourceServerTest extends AbstractIntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        FixtureFactory::initializeFixtures(
            $this->scopeManager,
            $this->clientManager,
            $this->accessTokenManager,
            $this->refreshTokenManager,
            $this->authCodeManager
        );
    }

    public function testValidAccessToken(): void
    {
        $existingAccessToken = $this->accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_PUBLIC);

        $request = $this->createResourceRequest(
            TestHelper::generateJwtToken($existingAccessToken)
        );

        $request = $this->handleResourceRequest($request);

        $this->assertSame(FixtureFactory::FIXTURE_ACCESS_TOKEN_PUBLIC, $request->getAttribute('oauth_access_token_id'));
        $this->assertSame(FixtureFactory::FIXTURE_CLIENT_FIRST, $request->getAttribute('oauth_client_id'));
        $this->assertSame('', $request->getAttribute('oauth_user_id'));
        $this->assertSame([], $request->getAttribute('oauth_scopes'));
    }

    public function testValidAccessTokenWithScopes(): void
    {
        $existingAccessToken = $this->accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_WITH_SCOPES);

        $request = $this->createResourceRequest(
            TestHelper::generateJwtToken($existingAccessToken)
        );

        $request = $this->handleResourceRequest($request);

        $this->assertSame(FixtureFactory::FIXTURE_ACCESS_TOKEN_WITH_SCOPES, $request->getAttribute('oauth_access_token_id'));
        $this->assertSame(FixtureFactory::FIXTURE_CLIENT_FIRST, $request->getAttribute('oauth_client_id'));
        $this->assertSame('', $request->getAttribute('oauth_user_id'));
        $this->assertSame([FixtureFactory::FIXTURE_SCOPE_FIRST], $request->getAttribute('oauth_scopes'));
    }

    public function testValidAccessTokenUserBound(): void
    {
        $existingAccessToken = $this->accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND);

        $request = $this->createResourceRequest(
            TestHelper::generateJwtToken($existingAccessToken)
        );

        $request = $this->handleResourceRequest($request);

        $this->assertSame(FixtureFactory::FIXTURE_ACCESS_TOKEN_USER_BOUND, $request->getAttribute('oauth_access_token_id'));
        $this->assertSame(FixtureFactory::FIXTURE_CLIENT_FIRST, $request->getAttribute('oauth_client_id'));
        $this->assertSame(FixtureFactory::FIXTURE_USER, $request->getAttribute('oauth_user_id'));
        $this->assertSame([], $request->getAttribute('oauth_scopes'));
    }

    public function testExpiredAccessToken(): void
    {
        $existingAccessToken = $this->accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED);

        $request = $this->createResourceRequest(
            TestHelper::generateJwtToken($existingAccessToken)
        );

        $request = $this->handleResourceRequest($request);

        $this->assertNull($request);
    }

    public function testRevokedAccessToken(): void
    {
        $existingAccessToken = $this->accessTokenManager->find(FixtureFactory::FIXTURE_ACCESS_TOKEN_REVOKED);

        $request = $this->createResourceRequest(
            TestHelper::generateJwtToken($existingAccessToken)
        );

        $request = $this->handleResourceRequest($request);

        $this->assertNull($request);
    }

    public function testValidClientCredentialsGrant(): void
    {
        $tokenResponse = $this->handleTokenRequest(
            $this->createAuthorizationRequest(null, [
                'client_id' => 'foo',
                'client_secret' => 'secret',
                'grant_type' => 'client_credentials',
            ])
        );

        $resourceRequest = $this->handleResourceRequest($this->createResourceRequest($tokenResponse['access_token']));
        $this->assertSame(FixtureFactory::FIXTURE_CLIENT_FIRST, $resourceRequest->getAttribute('oauth_client_id'));
        $this->assertSame('', $resourceRequest->getAttribute('oauth_user_id'));
        $this->assertSame([], $resourceRequest->getAttribute('oauth_scopes'));
    }
}
