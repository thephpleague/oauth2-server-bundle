<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection;

use Defuse\Crypto\Key;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use League\Bundle\OAuth2ServerBundle\DBAL\Type\Grant as GrantType;
use League\Bundle\OAuth2ServerBundle\DBAL\Type\RedirectUri as RedirectUriType;
use League\Bundle\OAuth2ServerBundle\DBAL\Type\Scope as ScopeType;
use League\Bundle\OAuth2ServerBundle\EventListener\ConvertExceptionToResponseListener;
use League\Bundle\OAuth2ServerBundle\League\AuthorizationServer\GrantTypeInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\ClientManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2TokenFactory;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker\DoctrineCredentialsRevoker;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\KernelEvents;

final class LeagueOAuth2ServerExtension extends Extension implements PrependExtensionInterface, CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $this->configurePersistence($loader, $container, $config['persistence']);
        $this->configureAuthorizationServer($container, $config['authorization_server']);
        $this->configureResourceServer($container, $config['resource_server']);
        $this->configureScopes($container, $config['scopes']);

        $container->findDefinition(OAuth2TokenFactory::class)
            ->setArgument(0, $config['role_prefix']);

        $container->findDefinition(ConvertExceptionToResponseListener::class)
            ->addTag('kernel.event_listener', [
                'event' => KernelEvents::EXCEPTION,
                'method' => 'onKernelException',
                'priority' => $config['exception_event_listener_priority'],
            ]);

        $container->registerForAutoconfiguration(GrantTypeInterface::class)
            ->addTag('league.oauth2_server.authorization_server.grant');
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'league_oauth2_server';
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => null,
                'types' => [
                    'oauth2_grant' => GrantType::class,
                    'oauth2_redirect_uri' => RedirectUriType::class,
                    'oauth2_scope' => ScopeType::class,
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->assertRequiredBundlesAreEnabled($container);
    }

    private function assertRequiredBundlesAreEnabled(ContainerBuilder $container): void
    {
        $requiredBundles = [
            'doctrine' => DoctrineBundle::class,
            'security' => SecurityBundle::class,
        ];

        foreach ($requiredBundles as $bundleAlias => $requiredBundle) {
            if (!$container->hasExtension($bundleAlias)) {
                throw new \LogicException(sprintf('Bundle \'%s\' needs to be enabled in your application kernel.', $requiredBundle));
            }
        }
    }

    private function configureAuthorizationServer(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('league.oauth2_server.encryption_key', $config['encryption_key']);
        $container->setParameter('league.oauth2_server.encryption_key.type', $config['encryption_key_type']);

        $authorizationServer = $container
            ->findDefinition(AuthorizationServer::class)
            ->replaceArgument(3, new Definition(CryptKey::class, [
                $config['private_key'],
                $config['private_key_passphrase'],
                false,
            ]));

        if ($config['enable_client_credentials_grant']) {
            $authorizationServer->addMethodCall('enableGrantType', [
                new Reference(ClientCredentialsGrant::class),
                new Definition(\DateInterval::class, [$config['access_token_ttl']]),
            ]);
        }

        if ($config['enable_password_grant']) {
            $authorizationServer->addMethodCall('enableGrantType', [
                new Reference(PasswordGrant::class),
                new Definition(\DateInterval::class, [$config['access_token_ttl']]),
            ]);
        }

        if ($config['enable_refresh_token_grant']) {
            $authorizationServer->addMethodCall('enableGrantType', [
                new Reference(RefreshTokenGrant::class),
                new Definition(\DateInterval::class, [$config['access_token_ttl']]),
            ]);
        }

        if ($config['enable_auth_code_grant']) {
            $authorizationServer->addMethodCall('enableGrantType', [
                new Reference(AuthCodeGrant::class),
                new Definition(\DateInterval::class, [$config['access_token_ttl']]),
            ]);
        }

        if ($config['enable_implicit_grant']) {
            $authorizationServer->addMethodCall('enableGrantType', [
                new Reference(ImplicitGrant::class),
                new Definition(\DateInterval::class, [$config['access_token_ttl']]),
            ]);
        }

        $this->configureGrants($container, $config);
    }

    private function configureGrants(ContainerBuilder $container, array $config): void
    {
        $container
            ->findDefinition(PasswordGrant::class)
            ->addMethodCall('setRefreshTokenTTL', [
                new Definition(\DateInterval::class, [$config['refresh_token_ttl']]),
            ])
        ;

        $container
            ->findDefinition(RefreshTokenGrant::class)
            ->addMethodCall('setRefreshTokenTTL', [
                new Definition(\DateInterval::class, [$config['refresh_token_ttl']]),
            ])
        ;

        $authCodeGrantDefinition = $container->findDefinition(AuthCodeGrant::class);
        $authCodeGrantDefinition->replaceArgument(2, new Definition(\DateInterval::class, [$config['auth_code_ttl']]))
            ->addMethodCall('setRefreshTokenTTL', [
                new Definition(\DateInterval::class, [$config['refresh_token_ttl']]),
            ])
        ;

        if (false === $config['require_code_challenge_for_public_clients']) {
            $authCodeGrantDefinition->addMethodCall('disableRequireCodeChallengeForPublicClients');
        }

        $container
            ->findDefinition(ImplicitGrant::class)
            ->replaceArgument(0, new Definition(\DateInterval::class, [$config['access_token_ttl']]))
        ;
    }

    /**
     * @throws \Exception
     */
    private function configurePersistence(LoaderInterface $loader, ContainerBuilder $container, array $config): void
    {
        if (\count($config) > 1) {
            throw new \LogicException('Only one persistence method can be configured at a time.');
        }

        $persistenceConfiguration = current($config);
        $persistenceMethod = key($config);

        switch ($persistenceMethod) {
            case 'in_memory':
                $loader->load('storage/in_memory.php');
                $this->configureInMemoryPersistence($container);
                break;
            case 'doctrine':
                $loader->load('storage/doctrine.php');
                $this->configureDoctrinePersistence($container, $persistenceConfiguration);
                break;
        }
    }

    private function configureDoctrinePersistence(ContainerBuilder $container, array $config): void
    {
        $entityManagerName = $config['entity_manager'];

        $entityManager = new Reference(
            sprintf('doctrine.orm.%s_entity_manager', $entityManagerName)
        );

        $container
            ->findDefinition(AccessTokenManager::class)
            ->replaceArgument(0, $entityManager)
        ;

        $container
            ->findDefinition(ClientManager::class)
            ->replaceArgument(0, $entityManager)
        ;

        $container
            ->findDefinition(RefreshTokenManager::class)
            ->replaceArgument(0, $entityManager)
        ;

        $container
            ->findDefinition(AuthorizationCodeManager::class)
            ->replaceArgument(0, $entityManager)
        ;

        $container
            ->findDefinition(DoctrineCredentialsRevoker::class)
            ->replaceArgument(0, $entityManager)
        ;

        $container->setParameter('league.oauth2_server.persistence.doctrine.enabled', true);
        $container->setParameter('league.oauth2_server.persistence.doctrine.manager', $entityManagerName);
    }

    private function configureInMemoryPersistence(ContainerBuilder $container): void
    {
        $container->setParameter('league.oauth2_server.persistence.in_memory.enabled', true);
    }

    private function configureResourceServer(ContainerBuilder $container, array $config): void
    {
        $container
            ->findDefinition(ResourceServer::class)
            ->replaceArgument(1, new Definition(CryptKey::class, [
                $config['public_key'],
                null,
                false,
            ]))
        ;
    }

    private function configureScopes(ContainerBuilder $container, array $scopes): void
    {
        $scopeManager = $container
            ->findDefinition(
                (string) $container->getAlias(ScopeManagerInterface::class)
            )
        ;

        foreach ($scopes as $scope) {
            $scopeManager->addMethodCall('save', [
                new Definition(ScopeModel::class, [$scope]),
            ]);
        }
    }
}
