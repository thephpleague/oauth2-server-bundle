<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('oauth2_authorization_server_metadata', '/.well-known/oauth-authorization-server%league.oauth2_server.metadata.path%')
        ->controller(['league.oauth2_server.controller.authorization_server_metadata', 'indexAction'])
        ->methods(['GET'])
    ;
};
