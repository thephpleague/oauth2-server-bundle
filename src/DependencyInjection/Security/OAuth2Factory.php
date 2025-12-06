<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\Security;

use League\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires the "oauth" authenticator from user configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class OAuth2Factory implements AuthenticatorFactoryInterface
{
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticator = \sprintf('security.authenticator.oauth2.%s', $firewallName);

        $definition = new ChildDefinition(OAuth2Authenticator::class);
        $definition->replaceArgument(2, new Reference($userProviderId));

        $container->setDefinition($authenticator, $definition);

        return $authenticator;
    }

    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getPriority(): int
    {
        return -10;
    }

    public function getKey(): string
    {
        return 'oauth2';
    }

    /**
     * @param ArrayNodeDefinition<TreeBuilder<'array'>> $builder
     */
    public function addConfiguration(NodeDefinition $builder): void
    {
    }
}
