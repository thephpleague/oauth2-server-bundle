<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeClientManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    public function __construct(
        string $environment,
        bool $debug,
        private ?array $resourceServiceConfig = null,
        private ?array $persistenceConfig = null,
    ) {
        parent::__construct($environment, $debug);
    }

    public function boot(): void
    {
        $this->initializeEnvironmentVariables();

        parent::boot();
    }

    public function registerBundles(): iterable
    {
        return [
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \League\Bundle\OAuth2ServerBundle\LeagueOAuth2ServerBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        $cacheDirectory = 'cache';

        // create unique cache directory when custom config is provided
        if (null !== $this->resourceServiceConfig || null !== $this->persistenceConfig) {
            $cacheDirectory = '/cache/' . hash('sha256', serialize(($this->resourceServiceConfig ?? []) + ($this->persistenceConfig ?? [])));
        }

        return \sprintf('%s/tests/.kernel/' . $cacheDirectory, $this->getProjectDir());
    }

    public function getLogDir(): string
    {
        return \sprintf('%s/tests/.kernel/logs', $this->getProjectDir());
    }

    public function process(ContainerBuilder $container): void
    {
        $this->exposeManagerServices($container);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $doctrine = [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'charset' => 'utf8mb4',
                    'url' => 'sqlite:///:memory:',
                    'default_table_options' => [
                        'charset' => 'utf8mb4',
                        'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                    ],
                ],
            ];

            $doctrine['orm'] = [];
            $container->loadFromExtension('doctrine', $doctrine);

            $framework = [
                'secret' => 'nope',
                'test' => null,
                'router' => [
                    'resource' => __DIR__ . '/Fixtures/routes.php',
                    'type' => 'php',
                    'utf8' => true,
                ],
                'http_method_override' => true,
                'php_errors' => ['log' => true],
            ];

            if (interface_exists(ValueResolverInterface::class)) {
                $framework['handle_all_throwables'] = true;
            }

            $container->loadFromExtension('framework', $framework);

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->setSynthetic(true)
                    ->setPublic(true)
                    ->addTag('routing.route_loader');
            }

            $security = [
                'firewalls' => [
                    'test' => [
                        'provider' => 'in_memory',
                        'pattern' => '^/security-test',
                        'stateless' => true,
                        'oauth2' => true,
                    ],
                    'authorization' => [
                        'provider' => 'in_memory',
                        'pattern' => '^/authorize',
                        'http_basic' => true,
                        'stateless' => true,
                    ],
                ],
                'providers' => [
                    'in_memory' => [
                        'memory' => [
                            'users' => [
                                FixtureFactory::FIXTURE_USER => [
                                    'roles' => ['ROLE_USER'],
                                ],
                                FixtureFactory::FIXTURE_USER_TWO => [
                                    'roles' => ['ROLE_USER'],
                                ],
                            ],
                        ],
                    ],
                    'another_provider' => [
                        'memory' => [
                            'users' => [
                                FixtureFactory::FIXTURE_USER => [
                                    'roles' => ['ROLE_USER'],
                                ],
                                FixtureFactory::FIXTURE_USER_TWO => [
                                    'roles' => ['ROLE_USER'],
                                ],
                            ],
                        ],
                    ],
                ],
                'access_control' => [
                    [
                        'path' => '^/authorize',
                        'roles' => 'IS_AUTHENTICATED',
                    ],
                    [
                        'path' => '^/device-code',
                        'roles' => 'IS_AUTHENTICATED',
                    ],
                ],
            ];

            $container->loadFromExtension('security', $security);

            $container->loadFromExtension('league_oauth2_server', [
                'authorization_server' => [
                    'private_key' => '%env(PRIVATE_KEY_PATH)%',
                    'encryption_key' => '%env(ENCRYPTION_KEY)%',
                ],
                'resource_server' => $this->resourceServiceConfig ?? ['public_key' => '%env(PUBLIC_KEY_PATH)%'],
                'scopes' => [
                    'available' => [
                        FixtureFactory::FIXTURE_SCOPE_FIRST,
                        FixtureFactory::FIXTURE_SCOPE_SECOND,
                    ],
                    'default' => [
                        FixtureFactory::FIXTURE_SCOPE_SECOND,
                    ],
                ],
                'persistence' => $this->persistenceConfig ?? ['doctrine' => ['entity_manager' => 'default']],
            ]);

            $this->configureControllers($container);
            $this->configureDatabaseServices($container);
            $this->configureCustomPersistenceServices($container);
            $this->registerFakeGrant($container);
        });
    }

    private function exposeManagerServices(ContainerBuilder $container): void
    {
        $container
            ->getAlias(ScopeManagerInterface::class)
            ->setPublic(true)
        ;

        $container
            ->getAlias(ClientManagerInterface::class)
            ->setPublic(true)
        ;

        $container
            ->getAlias(AccessTokenManagerInterface::class)
            ->setPublic(true)
        ;

        $container
            ->getAlias(RefreshTokenManagerInterface::class)
            ->setPublic(true)
        ;

        $container
            ->getAlias(AuthorizationCodeManagerInterface::class)
            ->setPublic(true)
        ;

        $container
            ->getAlias(DeviceCodeManagerInterface::class)
            ->setPublic(true)
        ;
    }

    private function configureControllers(ContainerBuilder $container): void
    {
        $container
            ->register(SecurityTestController::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
    }

    private function configureDatabaseServices(ContainerBuilder $container): void
    {
        $container
            ->register(SQLitePlatform::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
    }

    private function configureCustomPersistenceServices(ContainerBuilder $container): void
    {
        $container->register('test.access_token_manager', FakeAccessTokenManager::class)->setPublic(true);
        $container->register('test.authorization_code_manager', FakeAuthorizationCodeManager::class)->setPublic(true);
        $container->register('test.client_manager', FakeClientManager::class)->setPublic(true);
        $container->register('test.refresh_token_manager', FakeRefreshTokenManager::class)->setPublic(true);
        $container->register('test.credentials_revoker', FakeCredentialsRevoker::class)->setPublic(true);
        $container->register('test.device_code_manager', FakeDeviceCodeManager::class)->setPublic(true);
    }

    private function registerFakeGrant(ContainerBuilder $container): void
    {
        $container->register(FakeGrant::class)->setAutoconfigured(true);
    }

    private function initializeEnvironmentVariables(): void
    {
        putenv(\sprintf('PRIVATE_KEY_PATH=%s', TestHelper::PRIVATE_KEY_PATH));
        putenv(\sprintf('PUBLIC_KEY_PATH=%s', TestHelper::PUBLIC_KEY_PATH));
        putenv(\sprintf('ENCRYPTION_KEY=%s', TestHelper::ENCRYPTION_KEY));
    }
}
