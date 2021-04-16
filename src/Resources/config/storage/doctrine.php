<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\AuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\ClientManager;
use League\Bundle\OAuth2ServerBundle\Manager\Doctrine\RefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker\DoctrineCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
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

        ->set(DoctrineCredentialsRevoker::class)
            ->args([
                null,
            ])
        ->alias(CredentialsRevokerInterface::class, DoctrineCredentialsRevoker::class)
    ;
};
