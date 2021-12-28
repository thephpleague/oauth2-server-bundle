<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;
use League\Bundle\OAuth2ServerBundle\Repository\NullAccessTokenRepository;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('league.oauth2_server.repository.access_token', NullAccessTokenRepository::class)
        ->alias(AccessTokenRepositoryInterface::class, 'league.oauth2_server.repository.access_token')
        ->alias(AccessTokenRepository::class, 'league.oauth2_server.repository.access_token');
};
