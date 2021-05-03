<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\LeagueOAuth2ServerExtension;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\Security\OAuth2Factory;
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
        $extension->addSecurityListenerFactory(new OAuth2Factory());
    }

    private function configureDoctrineExtension(ContainerBuilder $container): void
    {
        /** @var string $modelCommonDirectory */
        $modelCommonDirectory = realpath(__DIR__ . '/Resources/config/doctrine/common');
        /** @var string $modelAccessTokenDirectory */
        $modelAccessTokenDirectory = realpath(__DIR__ . '/Resources/config/doctrine/access_token');
        /** @var string $modelNullDirectory */
        $modelNullDirectory = realpath(__DIR__ . '/Resources/config/doctrine/null');

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                [
                    $modelCommonDirectory => 'League\Bundle\OAuth2ServerBundle\Model',
                    $modelAccessTokenDirectory => 'League\Bundle\OAuth2ServerBundle\Model',
                ],
                [
                    'league.oauth2_server.persistence.doctrine.manager',
                ],
                'league.oauth2_server.persistence.doctrine.access_token.enabled',
                [
                    'LeagueOAuth2ServerBundle' => 'League\Bundle\OAuth2ServerBundle\Model',
                ]
            )
        );

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                [
                    $modelCommonDirectory => 'League\Bundle\OAuth2ServerBundle\Model',
                    $modelNullDirectory => 'League\Bundle\OAuth2ServerBundle\Model',
                ],
                [
                    'league.oauth2_server.persistence.doctrine.manager',
                ],
                'league.oauth2_server.persistence.doctrine.access_token.disabled',
                [
                    'LeagueOAuth2ServerBundle' => 'League\Bundle\OAuth2ServerBundle\Model',
                ]
            )
        );
    }
}
