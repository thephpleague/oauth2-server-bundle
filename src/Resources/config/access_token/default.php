<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\League\Repository\AccessTokenRepository;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

// BC Layer for < 5.1 versions
if (!function_exists('service')) {
    function service(string $id): ReferenceConfigurator
    {
        $fn = function_exists('Symfony\Component\DependencyInjection\Loader\Configurator\service')
            ? 'Symfony\Component\DependencyInjection\Loader\Configurator\service'
            : 'Symfony\Component\DependencyInjection\Loader\Configurator\ref';

        return ($fn)($id);
    }
}

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
