<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass;

use League\Bundle\OAuth2ServerBundle\Attribute\WithAccessTokenTTL;
use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface;
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
            // we only use the first tag
            // this allow to override autoregistration of interface to be able to set accessTokenTTL
            // since autoregistered tag are defined in last
            $attributes = array_shift($tags);

            try {
                $grantDefinition = $container->findDefinition($id);
                /** @var class-string|null $grantClass */
                $grantClass = $grantDefinition->getClass();
                if (null !== $grantClass) {
                    $refGrantClass = new \ReflectionClass($grantClass);

                    // skip of custom grant using \League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface
                    // since there are handled by \League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantConfigurator
                    // TODO remove code bloc when bundle interface and configurator will be deleted
                    if ($refGrantClass->implementsInterface(GrantTypeInterface::class)) {
                        continue;
                    }

                    // read of accessTokenTTL from WithAccessTokenTTL attribute
                    $withAccessTokenTTLAttributes = $refGrantClass->getAttributes(WithAccessTokenTTL::class);
                    if (count($withAccessTokenTTLAttributes) > 0) {
                        // we only use first attribute, because WithAccessTokenTTL is not repeatable
                        /** @var \ReflectionAttribute<WithAccessTokenTTL> $withAccessTokenTTLAttribute */
                        $withAccessTokenTTLAttributeArguments = array_shift($withAccessTokenTTLAttributes)->getArguments();
                        $attributes['accessTokenTTL'] = array_shift($withAccessTokenTTLAttributeArguments);
                    }
                }
            } catch (\ReflectionException) {
                // handling of this service as native one or without attribute
            }

            // use WithAccessTokenTTL value then accessTokenTTL tag attribute if exists, otherwise use global bundle config
            $accessTokenTTLValue = \array_key_exists('accessTokenTTL', $attributes)
                ? $attributes['accessTokenTTL']
                : $container->getParameter('league.oauth2_server.access_token_ttl.default');

            $definition->addMethodCall('enableGrantType', [
                new Reference($id),
                (is_string($accessTokenTTLValue))
                    ? new Definition(\DateInterval::class, [$accessTokenTTLValue])
                    : $accessTokenTTLValue,
            ]);
        }
    }
}
