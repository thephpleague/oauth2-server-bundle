<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\Security;

use League\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
trait OAuth2FactoryTrait
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint): array
    {
        throw new \LogicException('OAuth2 is not supported when "security.enable_authenticator_manager" is not set to true.');
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticator = sprintf('security.authenticator.oauth2.%s', $firewallName);

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

    public function addConfiguration(NodeDefinition $builder): void
    {
    }
}
