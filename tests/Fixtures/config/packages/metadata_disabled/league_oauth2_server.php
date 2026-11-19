<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('league_oauth2_server', [
        'authorization_server' => [
            'metadata' => ['enabled' => false],
        ],
    ]);
};
