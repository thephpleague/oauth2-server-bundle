<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\LeagueOAuth2ServerExtension;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\Security\LegacyOAuth2Factory;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\Security\OAuth2Factory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LeagueOAuth2ServerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $this->configureDoctrineExtension($container);
        $this->configureSecurityExtension($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new LeagueOAuth2ServerExtension();
    }

    private function configureSecurityExtension(ContainerBuilder $container): void
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        // BC Layer for < 5.1 versions
        $extension->addSecurityListenerFactory(interface_exists(AuthenticatorFactoryInterface::class) ? new OAuth2Factory() : new LegacyOAuth2Factory());
    }

    private function configureDoctrineExtension(ContainerBuilder $container): void
    {
        /** @var string $modelDirectory */
        $modelDirectory = realpath(__DIR__ . '/Resources/config/doctrine/model');

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                [
                    $modelDirectory => 'League\Bundle\OAuth2ServerBundle\Model',
                ],
                [
                    'league.oauth2_server.persistence.doctrine.manager',
                ],
                'league.oauth2_server.persistence.doctrine.enabled',
                [
                    'LeagueOAuth2ServerBundle' => 'League\Bundle\OAuth2ServerBundle\Model',
                ]
            )
        );
    }
}
