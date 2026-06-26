<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('league_oauth2_server', [
        'resource_server' => [
            'jwt_leeway' => 'PT60S',
        ],
    ]);
};
