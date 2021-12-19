<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\ClientManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Driver;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker\DoctrineCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('league.oauth2_server.persistence.driver', Driver::class)
            ->args([
                null,
                null,
            ])
        ->alias(Driver::class, 'league.oauth2_server.persistence.driver')

        ->set('league.oauth2_server.manager.doctrine.client', ClientManager::class)
            ->args([
                null,
                service(EventDispatcherInterface::class),
                null,
            ])
        ->alias(ClientManagerInterface::class, 'league.oauth2_server.manager.doctrine.client')
        ->alias(ClientManager::class, 'league.oauth2_server.manager.doctrine.client')

        ->set('league.oauth2_server.manager.doctrine.access_token', AccessTokenManager::class)
            ->args([
                null,
                null,
            ])
        ->alias(AccessTokenManagerInterface::class, 'league.oauth2_server.manager.doctrine.access_token')
        ->alias(AccessTokenManager::class, 'league.oauth2_server.manager.doctrine.access_token')

        ->set('league.oauth2_server.manager.doctrine.refresh_token', RefreshTokenManager::class)
            ->args([
                null,
            ])
        ->alias(RefreshTokenManagerInterface::class, 'league.oauth2_server.manager.doctrine.refresh_token')
        ->alias(RefreshTokenManager::class, 'league.oauth2_server.manager.doctrine.refresh_token')

        ->set('league.oauth2_server.manager.doctrine.authorization_code', AuthorizationCodeManager::class)
            ->args([
                null,
            ])
        ->alias(AuthorizationCodeManagerInterface::class, 'league.oauth2_server.manager.doctrine.authorization_code')
        ->alias(AuthorizationCodeManager::class, 'league.oauth2_server.manager.doctrine.authorization_code')

        ->set('league.oauth2_server.credentials_revoker.doctrine', DoctrineCredentialsRevoker::class)
            ->args([
                null,
                service(ClientManagerInterface::class),
            ])
        ->alias(CredentialsRevokerInterface::class, 'league.oauth2_server.credentials_revoker.doctrine')
        ->alias(DoctrineCredentialsRevoker::class, 'league.oauth2_server.credentials_revoker.doctrine')
    ;
};
