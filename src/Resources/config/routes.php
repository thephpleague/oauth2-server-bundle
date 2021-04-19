<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Controller\AuthorizationController;
use League\Bundle\OAuth2ServerBundle\Controller\TokenController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('oauth2_authorize', '/authorize')
        ->controller([AuthorizationController::class, 'indexAction'])

        ->add('oauth2_token', '/token')
        ->controller([TokenController::class, 'indexAction'])
        ->methods(['POST'])
    ;
};
