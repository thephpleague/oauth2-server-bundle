<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeClientManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;
use League\Bundle\OAuth2ServerBundle\Tests\TestKernel;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class CustomPersistenceManagerTest extends AbstractAcceptanceTest
{
    private AccessTokenManagerInterface&MockObject $accessTokenManager;
    private ClientManagerInterface&MockObject $clientManager;
    private RefreshTokenManagerInterface&MockObject $refreshTokenManager;
    private AuthorizationCodeManagerInterface&MockObject $authCodeManager;
    private DeviceCodeManagerInterface&MockObject $deviceCodeManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->clientManager = $this->createMock(ClientManagerInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->authCodeManager = $this->createMock(AuthorizationCodeManagerInterface::class);
        $this->deviceCodeManager = $this->createMock(DeviceCodeManagerInterface::class);
        $this->application = new Application($this->client->getKernel());
    }

    public function testRegisteredServices(): void
    {
        static::assertInstanceOf(FakeAccessTokenManager::class, $this->client->getContainer()->get(AccessTokenManagerInterface::class));
        static::assertInstanceOf(FakeAuthorizationCodeManager::class, $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class));
        static::assertInstanceOf(FakeClientManager::class, $this->client->getContainer()->get(ClientManagerInterface::class));
        static::assertInstanceOf(FakeRefreshTokenManager::class, $this->client->getContainer()->get(RefreshTokenManagerInterface::class));
        static::assertInstanceOf(FakeCredentialsRevoker::class, $this->client->getContainer()->get(CredentialsRevokerInterface::class));
        static::assertInstanceOf(FakeDeviceCodeManager::class, $this->client->getContainer()->get(DeviceCodeManagerInterface::class));
    }

    public function testSuccessfulClientCredentialsRequest(): void
    {
        $this->accessTokenManager->expects(self::atLeastOnce())->method('find')->willReturn(null);
        $this->accessTokenManager->expects(self::atLeastOnce())->method('save');
        $this->client->getContainer()->set('test.access_token_manager', $this->accessTokenManager);

        $this->clientManager->expects(self::atLeastOnce())->method('find')->with('foo')->willReturn(new Client('name', 'foo', 'secret'));
        $this->client->getContainer()->set('test.client_manager', $this->clientManager);

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'client_credentials',
        ]);

        $this->client->getResponse();
        static::assertResponseIsSuccessful();
    }

    public function testSuccessfulPasswordRequest(): void
    {
        $this->accessTokenManager->expects(self::atLeastOnce())->method('find')->willReturn(null);
        $this->accessTokenManager->expects(self::atLeastOnce())->method('save');
        $this->client->getContainer()->set('test.access_token_manager', $this->accessTokenManager);

        $this->clientManager->expects(self::atLeastOnce())->method('find')->with('foo')->willReturn(new Client('name', 'foo', 'secret'));
        $this->client->getContainer()->set('test.client_manager', $this->clientManager);

        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(OAuth2Events::USER_RESOLVE, static function (UserResolveEvent $event): void {
            $event->setUser(FixtureFactory::createUser());
        });

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'password',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->client->getResponse();
        static::assertResponseIsSuccessful();
    }

    public function testSuccessfulRefreshTokenRequest(): void
    {
        $client = new Client('name', 'foo', 'secret');
        $accessToken = new AccessToken('access_token', new \DateTimeImmutable('+1 hour'), $client, 'user', []);
        $refreshToken = new RefreshToken('refresh_token', new \DateTimeImmutable('+1 month'), $accessToken);

        $this->refreshTokenManager->expects(self::atLeastOnce())->method('find')->willReturn($refreshToken, null);
        $this->client->getContainer()->set('test.refresh_token_manager', $this->refreshTokenManager);

        $this->accessTokenManager->expects(self::atLeastOnce())->method('find')->willReturn($accessToken, null);
        $this->accessTokenManager->expects(self::atLeastOnce())->method('save');
        $this->client->getContainer()->set('test.access_token_manager', $this->accessTokenManager);

        $this->clientManager->expects(self::atLeastOnce())->method('find')->with('foo')->willReturn($client);
        $this->client->getContainer()->set('test.client_manager', $this->clientManager);

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'refresh_token',
            'refresh_token' => TestHelper::generateEncryptedPayload($refreshToken),
        ]);

        $this->client->getResponse();
        static::assertResponseIsSuccessful();
    }

    public function testSuccessfulAuthorizationCodeRequest(): void
    {
        $client = new Client('name', 'foo', 'secret');
        $client->setRedirectUris(new RedirectUri('https://example.org/oauth2/redirect-uri'));
        $authCode = new AuthorizationCode('authorization_code', new \DateTimeImmutable('+2 minute'), $client, 'user', []);

        $this->authCodeManager->expects(self::atLeastOnce())->method('find')->willReturn($authCode, null);
        $this->client->getContainer()->set('test.authorization_code_manager', $this->authCodeManager);

        $this->accessTokenManager->expects(self::atLeastOnce())->method('find')->willReturn(null);
        $this->accessTokenManager->expects(self::atLeastOnce())->method('save');
        $this->client->getContainer()->set('test.access_token_manager', $this->accessTokenManager);

        $this->clientManager->expects(self::atLeastOnce())->method('find')->with('foo')->willReturn($client);
        $this->client->getContainer()->set('test.client_manager', $this->clientManager);

        $this->client->request('POST', '/token', [
            'client_id' => 'foo',
            'client_secret' => 'secret',
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://example.org/oauth2/redirect-uri',
            'code' => TestHelper::generateEncryptedAuthCodePayload($authCode),
        ]);

        $this->client->getResponse();
        static::assertResponseIsSuccessful();
    }

    public function testSuccessfullDeviceCodeRequest(): void
    {
        $client = new Client('name', 'foo', 'secret');

        $this->deviceCodeManager->expects(self::atLeastOnce())->method('find')->willReturn(null);
        $this->deviceCodeManager->expects(self::atLeastOnce())->method('save');
        $this->client->getContainer()->set('test.device_code_manager', $this->deviceCodeManager);

        $this->clientManager->expects(self::atLeastOnce())->method('find')->with('foo')->willReturn($client);
        $this->client->getContainer()->set('test.client_manager', $this->clientManager);

        $this->client->request('POST', '/device-code', [
            'client_id' => $client->getIdentifier(),
        ]);

        $this->client->getResponse();
        static::assertResponseIsSuccessful();
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel(
            'test',
            false,
            null,
            [
                'custom' => [
                    'access_token_manager' => 'test.access_token_manager',
                    'authorization_code_manager' => 'test.authorization_code_manager',
                    'client_manager' => 'test.client_manager',
                    'refresh_token_manager' => 'test.refresh_token_manager',
                    'credentials_revoker' => 'test.credentials_revoker',
                    'device_code_manager' => 'test.device_code_manager',
                ],
            ]
        );
    }
}
