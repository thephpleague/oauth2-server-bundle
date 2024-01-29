<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass\EncryptionKeyPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\LeagueOAuth2ServerExtension;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\Security\OAuth2Factory;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Doctrine\ODM\Driver as DoctrineODMDriver;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Doctrine\ORM\Driver as DoctrineORMDriver;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LeagueOAuth2ServerBundle extends Bundle
{
    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $this->configureDoctrineExtension($container);
        $this->configureSecurityExtension($container);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new LeagueOAuth2ServerExtension();
    }

    /**
     * @psalm-suppress UndefinedMethod
     */
    private function configureSecurityExtension(ContainerBuilder $container): void
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        if (method_exists($extension, 'addAuthenticatorFactory')) {
            $extension->addAuthenticatorFactory(new OAuth2Factory());

            return;
        }

        /**
         * @psalm-suppress DeprecatedMethod
         * @psalm-suppress InvalidArgument
         */
        $extension->addSecurityListenerFactory(new OAuth2Factory());
    }

    private function configureDoctrineExtension(ContainerBuilder $container): void
    {
        if (class_exists(DoctrineOrmMappingsPass::class)) {
            $container->addCompilerPass(
                new DoctrineOrmMappingsPass(
                    new Reference(DoctrineORMDriver::class),
                    ['League\Bundle\OAuth2ServerBundle\Model'],
                    ['league.oauth2_server.persistence.doctrine.manager'],
                    'league.oauth2_server.persistence.doctrine.enabled'
                )
            );
        }
        if (class_exists(DoctrineMongoDBMappingsPass::class)) {
            $container->addCompilerPass(
                new DoctrineMongoDBMappingsPass(
                    new Reference(DoctrineODMDriver::class),
                    ['League\Bundle\OAuth2ServerBundle\Model'],
                    ['league.oauth2_server.persistence.doctrine_odm.manager'],
                    'league.oauth2_server.persistence.doctrine_odm.enabled'
                )
            );
        }

        $container->addCompilerPass(new EncryptionKeyPass());
    }
}
