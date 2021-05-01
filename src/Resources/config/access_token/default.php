<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('league.oauth2_server.repository.access_token', AccessTokenRepository::class)
            ->args([
                service(AccessTokenManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverterInterface::class),
            ])
        ->alias(AccessTokenRepositoryInterface::class, 'league.oauth2_server.repository.access_token')
        ->alias(AccessTokenRepository::class, 'league.oauth2_server.repository.access_token');
};
