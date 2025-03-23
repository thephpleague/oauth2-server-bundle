<?php declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass;

use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface;
use League\OAuth2\Server\AuthorizationServer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GrantTypePass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container): void
    {
        // check if AuthorizationServer service is defined
        if (!$container->has(AuthorizationServer::class)) {
            return;
        }

        $definition = $container->findDefinition(AuthorizationServer::class);

        // find all service IDs with the league.oauth2_server.authorization_server.grant tag
        $taggedServices = $container->findTaggedServiceIds('league.oauth2_server.authorization_server.grant');

        // enable grant type for each
        foreach ($taggedServices as $id => $tags) {
            // skip of custom grant using \League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface
            // since there are handled by \League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantConfigurator
            // TODO remove code bloc when bundle interface and configurator will be deleted
            try {
                $grantDefinition = $container->findDefinition($id);
                /** @var class-string|null $grantClass */
                $grantClass = $grantDefinition->getClass();
                if (null !== $grantClass) {
                    $refGrantClass = new \ReflectionClass($grantClass);
                    if ($refGrantClass->implementsInterface(GrantTypeInterface::class)) {
                        continue;
                    }
                }
            } catch (\ReflectionException) {
                // handling of this service as native one
            }

            foreach ($tags as $attributes) {
                $definition->addMethodCall('enableGrantType', [
                    new Reference($id),
                    // use accessTokenTTL tag attribute if exists, otherwise use global bundle config
                    new Definition(\DateInterval::class, [\array_key_exists('accessTokenTTL', $attributes)
                        ? $attributes['accessTokenTTL']
                        : $container->getParameter('league.oauth2_server.access_token_ttl.default')]),
                ]);
            }
        }
    }
}
