<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        // RFC 8414 section 3.1: the well-known segment goes between the host and the path of the issuer
        ->add('oauth2_authorization_server_metadata', '/.well-known/oauth-authorization-server{issuerPath}')
        ->controller(['league.oauth2_server.controller.authorization_server_metadata', 'indexAction'])
        ->defaults(['issuerPath' => ''])
        ->requirements(['issuerPath' => '(/.*)?'])
        ->methods(['GET'])
    ;
};
