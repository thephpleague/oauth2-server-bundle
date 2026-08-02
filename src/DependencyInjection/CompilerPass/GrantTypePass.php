<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass;

use League\OAuth2\Server\AuthorizationServer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GrantTypePass implements CompilerPassInterface
{
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
            foreach ($tags as $attributes) {
                // use accessTokenTTL tag attribute if exists, otherwise use global bundle config
                $accessTokenTTLValue = \array_key_exists('accessTokenTTL', $attributes)
                    ? $attributes['accessTokenTTL']
                    : $container->getParameter('league.oauth2_server.access_token_ttl.default');

                $definition->addMethodCall('enableGrantType', [
                    new Reference($id),
                    (\is_string($accessTokenTTLValue))
                        ? new Definition(\DateInterval::class, [$accessTokenTTLValue])
                        : $accessTokenTTLValue,
                ]);
            }
        }
    }
}
