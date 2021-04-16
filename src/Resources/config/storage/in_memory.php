<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ClientManager;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(ClientManager::class)
            ->args([
                null,
            ])
        ->alias(ClientManagerInterface::class, ClientManager::class)

        ->set(AccessTokenManager::class)
            ->args([
                null,
            ])
        ->alias(AccessTokenManagerInterface::class, AccessTokenManager::class)

        ->set(RefreshTokenManager::class)
            ->args([
                null,
            ])
        ->alias(RefreshTokenManagerInterface::class, RefreshTokenManager::class)

        ->set(AuthorizationCodeManager::class)
            ->args([
                null,
            ])
        ->alias(AuthorizationCodeManagerInterface::class, AuthorizationCodeManager::class)
    ;
};
