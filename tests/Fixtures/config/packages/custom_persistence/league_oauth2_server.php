<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('league_oauth2_server', [
        'persistence' => [
            'custom' => [
                'access_token_manager' => 'test.access_token_manager',
                'authorization_code_manager' => 'test.authorization_code_manager',
                'client_manager' => 'test.client_manager',
                'refresh_token_manager' => 'test.refresh_token_manager',
                'credentials_revoker' => 'test.credentials_revoker',
                'device_code_manager' => 'test.device_code_manager',
            ],
        ],
    ]);
};
