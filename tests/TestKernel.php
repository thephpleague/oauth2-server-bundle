<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->initializeEnvironmentVariables();

        parent::boot();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \League\Bundle\OAuth2ServerBundle\LeagueOAuth2ServerBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sprintf('%s/tests/.kernel/cache', $this->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sprintf('%s/tests/.kernel/logs', $this->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->exposeManagerServices($container);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'sqlite',
                    'charset' => 'utf8mb4',
                    'url' => 'sqlite:///:memory:',
                    'default_table_options' => [
                        'charset' => 'utf8mb4',
                        'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                    ],
                    'platform_service' => SqlitePlatform::class,
                ],
                'orm' => null,
            ]);

            $container->loadFromExtension('framework', [
                'secret' => 'nope',
                'test' => null,
                'router' => [
                    'resource' => __DIR__ . '/Fixtures/routes.php',
                    'type' => 'php',
                    'utf8' => true,
                ],
            ]);

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->setSynthetic(true)
                    ->setPublic(true)
                    ->addTag('routing.route_loader');
            }

            $container->loadFromExtension('security', [
                'firewalls' => [
                    'test' => [
                        'pattern' => '^/security-test',
                        'stateless' => true,
                        'oauth2' => true,
                    ],
                ],
                'providers' => [
                    'in_memory' => [
                        'memory' => [
                            'users' => [
                                FixtureFactory::FIXTURE_USER => [
                                    'roles' => ['ROLE_USER'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $container->loadFromExtension('league_oauth2_server', [
                'authorization_server' => [
                    'private_key' => '%env(PRIVATE_KEY_PATH)%',
                    'encryption_key' => '%env(ENCRYPTION_KEY)%',
                ],
                'resource_server' => [
                    'public_key' => '%env(PUBLIC_KEY_PATH)%',
                ],
                'scopes' => [
                    FixtureFactory::FIXTURE_SCOPE_SECOND,
                ],
                'persistence' => [
                    'doctrine' => [
                        'entity_manager' => 'default',
                    ],
                ],
            ]);

            $this->configureControllers($container);
            $this->configureDatabaseServices($container);
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
            ->register(SqlitePlatform::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
    }

    private function registerFakeGrant(ContainerBuilder $container): void
    {
        $container->register(FakeGrant::class)->setAutoconfigured(true);
    }

    private function initializeEnvironmentVariables(): void
    {
        putenv(sprintf('PRIVATE_KEY_PATH=%s', TestHelper::PRIVATE_KEY_PATH));
        putenv(sprintf('PUBLIC_KEY_PATH=%s', TestHelper::PUBLIC_KEY_PATH));
        putenv(sprintf('ENCRYPTION_KEY=%s', TestHelper::ENCRYPTION_KEY));
    }
}
