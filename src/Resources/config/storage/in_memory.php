<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ClientManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('league.oauth2_server.manager.in_memory.client', ClientManager::class)
            ->args([
                service(EventDispatcherInterface::class),
            ])
        ->alias(ClientManagerInterface::class, 'league.oauth2_server.manager.in_memory.client')
        ->alias(ClientManager::class, 'league.oauth2_server.manager.in_memory.client')

        ->set('league.oauth2_server.manager.in_memory.access_token', AccessTokenManager::class)
            ->args([
                null,
            ])
        ->alias(AccessTokenManagerInterface::class, 'league.oauth2_server.manager.in_memory.access_token')
        ->alias(AccessTokenManager::class, 'league.oauth2_server.manager.in_memory.access_token')

        ->set('league.oauth2_server.manager.in_memory.refresh_token', RefreshTokenManager::class)
            ->args([
                null,
            ])
        ->alias(RefreshTokenManagerInterface::class, 'league.oauth2_server.manager.in_memory.refresh_token')
        ->alias(RefreshTokenManager::class, 'league.oauth2_server.manager.in_memory.refresh_token')

        ->set('league.oauth2_server.manager.in_memory.authorization_code', AuthorizationCodeManager::class)
            ->args([
                null,
            ])
        ->alias(AuthorizationCodeManagerInterface::class, 'league.oauth2_server.manager.in_memory.authorization_code')
        ->alias(AuthorizationCodeManager::class, 'league.oauth2_server.manager.in_memory.authorization_code')
    ;
};
