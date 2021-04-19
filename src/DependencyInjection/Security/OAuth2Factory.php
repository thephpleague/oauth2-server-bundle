<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\Security;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Provider\OAuth2Provider;
use League\Bundle\OAuth2ServerBundle\Security\EntryPoint\OAuth2EntryPoint;
use League\Bundle\OAuth2ServerBundle\Security\Firewall\OAuth2Listener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2Factory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.oauth2.' . $id;
        $container
            ->setDefinition($providerId, new ChildDefinition(OAuth2Provider::class))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(5, $id);

        $listenerId = 'security.authentication.listener.oauth2.' . $id;
        $container
            ->setDefinition($listenerId, new ChildDefinition(OAuth2Listener::class))
            ->replaceArgument(4, $id);

        return [$providerId, $listenerId, OAuth2EntryPoint::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function addConfiguration(NodeDefinition $node)
    {
    }
}
