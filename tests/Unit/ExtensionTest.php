<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Controller\AuthorizationServerMetadataController;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\LeagueOAuth2ServerExtension;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ScopeManager;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\DeviceCodeGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class ExtensionTest extends TestCase
{
    #[DataProvider('grantsProvider')]
    public function testEnablingAndDisablingGrants(string $referenceId, string $grantKey, bool $shouldTheGrantBeEnabled): void
    {
        $container = new ContainerBuilder();

        $this->setupContainer($container);

        $extension = new LeagueOAuth2ServerExtension();

        $extension->load($this->getValidConfiguration([$grantKey => $shouldTheGrantBeEnabled]), $container);

        $authorizationServer = $container->findDefinition(AuthorizationServer::class);
        $methodCalls = $authorizationServer->getMethodCalls();
        $isGrantEnabled = false;

        foreach ($methodCalls as $methodCall) {
            if ('enableGrantType' === $methodCall[0] && $referenceId === (string) $methodCall[1][0]) {
                $isGrantEnabled = true;
                break;
            }
        }

        $this->assertSame($shouldTheGrantBeEnabled, $isGrantEnabled);
    }

    public static function grantsProvider(): iterable
    {
        yield 'Client credentials grant can be enabled' => [
            ClientCredentialsGrant::class, 'enable_client_credentials_grant', true,
        ];
        yield 'Client credentials grant can be disabled' => [
            ClientCredentialsGrant::class, 'enable_client_credentials_grant', false,
        ];
        yield 'Password grant can be enabled' => [
            PasswordGrant::class, 'enable_password_grant', true,
        ];
        yield 'Password grant can be disabled' => [
            PasswordGrant::class, 'enable_password_grant', false,
        ];
        yield 'Refresh token grant can be enabled' => [
            RefreshTokenGrant::class, 'enable_refresh_token_grant', true,
        ];
        yield 'Refresh token grant can be disabled' => [
            RefreshTokenGrant::class, 'enable_refresh_token_grant', false,
        ];
        yield 'Device code grant can be enabled' => [
            DeviceCodeGrant::class, 'enable_device_code_grant', true,
        ];
        yield 'Device code grant can be disabled' => [
            DeviceCodeGrant::class, 'enable_device_code_grant', false,
        ];
    }

    public function testDeviceCodeGrantIsDisabledByDefault(): void
    {
        $container = new ContainerBuilder();

        $this->setupContainer($container);

        $extension = new LeagueOAuth2ServerExtension();

        $config = $this->getValidConfiguration();
        unset($config[0]['authorization_server']['enable_device_code_grant']);

        $extension->load($config, $container);

        $authorizationServer = $container->findDefinition(AuthorizationServer::class);
        $enabledGrants = [];

        foreach ($authorizationServer->getMethodCalls() as $methodCall) {
            if ('enableGrantType' === $methodCall[0]) {
                $enabledGrants[(string) $methodCall[1][0]] = (string) $methodCall[1][0];
            }
        }

        $this->assertArrayNotHasKey(DeviceCodeGrant::class, $enabledGrants);
    }

    #[DataProvider('requireCodeChallengeForPublicClientsProvider')]
    public function testAuthCodeGrantDisableRequireCodeChallengeForPublicClientsConfig(
        ?bool $requireCodeChallengeForPublicClients,
        bool $shouldTheRequirementBeDisabled,
    ): void {
        $container = new ContainerBuilder();

        $this->setupContainer($container);

        $extension = new LeagueOAuth2ServerExtension();

        $configuration = $this->getValidConfiguration();
        $configuration[0]['authorization_server']['require_code_challenge_for_public_clients'] = $requireCodeChallengeForPublicClients;

        $extension->load($configuration, $container);

        $authorizationServer = $container->findDefinition(AuthCodeGrant::class);
        $methodCalls = $authorizationServer->getMethodCalls();

        $isRequireCodeChallengeForPublicClientsDisabled = false;

        foreach ($methodCalls as $methodCall) {
            if ('disableRequireCodeChallengeForPublicClients' === $methodCall[0]) {
                $isRequireCodeChallengeForPublicClientsDisabled = true;
                break;
            }
        }

        $this->assertSame($shouldTheRequirementBeDisabled, $isRequireCodeChallengeForPublicClientsDisabled);
    }

    public static function requireCodeChallengeForPublicClientsProvider(): iterable
    {
        yield 'when not requiring code challenge for public clients the requirement should be disabled' => [
            false, true,
        ];
        yield 'when code challenge for public clients is required the requirement should not be disabled' => [
            true, false,
        ];
        yield 'with the default value the requirement should not be disabled' => [
            null, false,
        ];
    }

    #[DataProvider('scopeProvider')]
    public function testDefaultScopeValidation(array $available, array $default, bool $valid): void
    {
        $container = new ContainerBuilder();
        $extension = new LeagueOAuth2ServerExtension();

        $this->setupContainer($container);

        if (!$valid) {
            $this->expectException(\LogicException::class);
        }

        $extension->load($this->getValidConfiguration(['scopes' => ['available' => $available, 'default' => $default]]), $container);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('revokeRefreshTokensProvider')]
    public function testEnablingAndDisablingRevocationOfRefreshTokens(bool $shouldRevokeRefreshTokens): void
    {
        $container = new ContainerBuilder();
        $extension = new LeagueOAuth2ServerExtension();

        $extension->load($this->getValidConfiguration(['revoke_refresh_tokens' => $shouldRevokeRefreshTokens]), $container);

        $authorizationServer = $container->findDefinition(AuthorizationServer::class);
        $methodCalls = $authorizationServer->getMethodCalls();
        $revokeRefreshTokens = null;

        foreach ($methodCalls as $methodCall) {
            if ('revokeRefreshTokens' === $methodCall[0]) {
                $revokeRefreshTokens = $methodCall[1][0];
                break;
            }
        }

        $this->assertSame($shouldRevokeRefreshTokens, $revokeRefreshTokens);
    }

    public function testTheMetadataAreOffByDefault(): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(), $container);

        $this->assertSame('', $container->getParameter('league.oauth2_server.metadata.path'));
        $this->assertNull($container->findDefinition(AuthorizationServerMetadataController::class)->getArgument(2));
    }

    #[DataProvider('issuerPathProvider')]
    public function testTheRouteOfTheMetadataFollowsThePathOfTheIssuer(string $issuer, string $expectedPath, string $expectedOrigin): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(['metadata' => ['enabled' => true, 'issuer' => $issuer]]), $container);

        $this->assertSame($expectedPath, $container->getParameter('league.oauth2_server.metadata.path'));
        $this->assertSame($issuer, $container->findDefinition(AuthorizationServerMetadataController::class)->getArgument(2));
        $this->assertSame($expectedOrigin, $container->findDefinition(AuthorizationServerMetadataController::class)->getArgument(3));
    }

    public static function issuerPathProvider(): iterable
    {
        yield 'without a path' => ['https://example.com', '', 'https://example.com'];
        yield 'with a trailing slash only' => ['https://example.com/', '', 'https://example.com'];
        yield 'with a path' => ['https://example.com/auth', '/auth', 'https://example.com'];
        yield 'with a nested path' => ['https://example.com/admin/mcp/', '/admin/mcp', 'https://example.com'];
        yield 'with a port' => ['https://example.com:8443/auth', '/auth', 'https://example.com:8443'];
        yield 'on localhost over http' => ['http://localhost:8000', '', 'http://localhost:8000'];
        yield 'on 127.0.0.1 over http' => ['http://127.0.0.1:8000', '', 'http://127.0.0.1:8000'];
        yield 'on an IPv6 loopback over http' => ['http://[::1]:8000', '', 'http://[::1]:8000'];
        yield 'on an IPv6 host' => ['https://[2001:db8::1]:8443/auth', '/auth', 'https://[2001:db8::1]:8443'];
        yield 'with upper case scheme and host' => ['HTTPS://Example.COM/auth', '/auth', 'https://example.com'];
    }

    #[DataProvider('invalidIssuerProvider')]
    public function testInvalidIssuersAreRejected(?string $issuer): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        $this->expectException(InvalidConfigurationException::class);

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(['metadata' => ['enabled' => true, 'issuer' => $issuer]]), $container);
    }

    public static function invalidIssuerProvider(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'not a url' => ['example.com'];
        yield 'plain http' => ['http://example.com'];
        yield 'with a query' => ['https://example.com/auth?tenant=1'];
        yield 'with a fragment' => ['https://example.com/auth#top'];
        yield 'with credentials' => ['https://user@example.com'];
        yield 'with a space in the host' => ['https://exa mple.com'];
        yield 'with a trailing line break' => ["https://example.com\n"];
        yield 'with a wildcard host' => ['https://*.example.com'];
        yield 'with port zero' => ['https://example.com:0'];
        yield 'with a space in the path' => ['https://example.com/a b'];
        yield 'with a placeholder in the path' => ['https://example.com/{tenant}'];
        yield 'with an encoded slash in the path' => ['https://example.com/a%2Fb'];
        yield 'with plain http on an upper case host' => ['http://EXAMPLE.com'];
    }

    #[DataProvider('placeholderIssuerProvider')]
    public function testAParameterOrEnvironmentVariableAsIssuerSaysWhyItIsRejected(string $issuer): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a literal URL');

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(['metadata' => ['enabled' => true, 'issuer' => $issuer]]), $container);
    }

    public static function placeholderIssuerProvider(): iterable
    {
        yield 'an environment variable' => ['%env(OAUTH_ISSUER)%'];
        yield 'a parameter' => ['https://%app.host%'];
    }

    public function testTheMetadataNeedNoIssuerWhileTheyAreOff(): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(['metadata' => ['enabled' => false, 'issuer' => '%env(OAUTH_ISSUER)%']]), $container);

        $this->assertNull($container->findDefinition(AuthorizationServerMetadataController::class)->getArgument(2));
    }

    public function testTheMetadataControllerIsDroppedWhenTheApplicationHasNoRouter(): void
    {
        $container = $this->containerForProcessing();

        (new LeagueOAuth2ServerExtension())->process($container);

        $this->assertFalse($container->has(AuthorizationServerMetadataController::class));
        $this->assertFalse($container->hasDefinition('league.oauth2_server.controller.authorization_server_metadata'));
    }

    public function testTheMetadataControllerIsKeptWhenTheApplicationHasARouter(): void
    {
        $container = $this->containerForProcessing();
        $container->register('router');

        (new LeagueOAuth2ServerExtension())->process($container);

        $this->assertTrue($container->hasDefinition('league.oauth2_server.controller.authorization_server_metadata'));
        $this->assertTrue($container->hasAlias(AuthorizationServerMetadataController::class));
    }

    private function containerForProcessing(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);
        $container->registerExtension(new class extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'security';
            }
        });

        (new LeagueOAuth2ServerExtension())->load($this->getValidConfiguration(), $container);

        return $container;
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('authMethodsProvider')]
    public function testTheClientAuthenticationMethodsFollowTheEnabledGrants(array $options, ?array $expected): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        $configuration = $this->getValidConfiguration($options + ['metadata' => ['enabled' => true, 'issuer' => 'https://example.com']]);
        $configuration[0]['authorization_server']['enable_auth_code_grant'] = $options['enable_auth_code_grant'] ?? true;

        (new LeagueOAuth2ServerExtension())->load($configuration, $container);

        $metadata = $container->findDefinition(AuthorizationServerMetadataController::class)->getArgument(4);

        $this->assertSame($expected, $metadata['token_endpoint_auth_methods_supported'] ?? null);
    }

    public static function authMethodsProvider(): iterable
    {
        yield 'with the authorization code grant' => [[], ['client_secret_basic', 'client_secret_post', 'none']];
        yield 'with client credentials only' => [['enable_auth_code_grant' => false, 'enable_refresh_token_grant' => false], ['client_secret_basic', 'client_secret_post']];
        yield 'with the implicit grant only' => [['enable_auth_code_grant' => false, 'enable_refresh_token_grant' => false, 'enable_client_credentials_grant' => false, 'enable_implicit_grant' => true], null];
    }

    public function testTheMetadataOnlyOfferTheEndpointsOfTheEnabledGrants(): void
    {
        $container = new ContainerBuilder();
        $this->setupContainer($container);

        $configuration = $this->getValidConfiguration(['enable_refresh_token_grant' => false, 'metadata' => ['enabled' => true, 'issuer' => 'https://example.com']]);
        $configuration[0]['authorization_server']['enable_auth_code_grant'] = false;

        (new LeagueOAuth2ServerExtension())->load($configuration, $container);

        $definition = $container->findDefinition(AuthorizationServerMetadataController::class);

        $this->assertSame(['token_endpoint' => 'oauth2_token'], $definition->getArgument(5));
        $this->assertSame(['client_credentials'], $definition->getArgument(4)['grant_types_supported']);
        $this->assertSame([], $definition->getArgument(4)['response_types_supported']);
        $this->assertArrayNotHasKey('code_challenge_methods_supported', $definition->getArgument(4));
    }

    public static function scopeProvider(): iterable
    {
        yield 'when a default scope is part of available scopes' => [
            ['scope_one', 'scope_two'],
            ['scope_one'],
            true,
        ];

        yield 'when a default scope is not part of available scopes' => [
            ['scope_one', 'scope_two'],
            ['unknown_scope'],
            false,
        ];
    }

    private function getValidConfiguration(array $options = []): array
    {
        return [
            [
                'authorization_server' => [
                    'private_key' => 'foo',
                    'encryption_key' => 'foo',
                    'enable_client_credentials_grant' => $options['enable_client_credentials_grant'] ?? true,
                    'enable_password_grant' => $options['enable_password_grant'] ?? false,
                    'enable_implicit_grant' => $options['enable_implicit_grant'] ?? false,
                    'enable_refresh_token_grant' => $options['enable_refresh_token_grant'] ?? true,
                    'enable_device_code_grant' => $options['enable_device_code_grant'] ?? false,
                    'revoke_refresh_tokens' => $options['revoke_refresh_tokens'] ?? true,
                ] + (isset($options['metadata']) ? ['metadata' => $options['metadata']] : []),
                'resource_server' => [
                    'public_key' => 'foo',
                ],
                'scopes' => $options['scopes'] ?? [
                    'available' => [
                        'foo',
                        'bar',
                    ],
                    'default' => [
                        'foo',
                    ],
                ],
                // Pick one for valid config:
                // 'persistence' => ['doctrine' => []]
                'persistence' => ['in_memory' => 1],
            ],
        ];
    }

    public static function revokeRefreshTokensProvider(): iterable
    {
        yield 'do revoke refresh tokens' => [true];
        yield 'do not revoke refresh tokens' => [false];
    }

    private function setupContainer(ContainerBuilder $container): void
    {
        $container->register(ScopeManager::class);
    }
}
