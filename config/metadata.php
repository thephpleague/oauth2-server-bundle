<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use League\Bundle\OAuth2ServerBundle\Controller\AuthorizationServerMetadataController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Issuer, metadata and endpoints are set by the extension
        ->set('league.oauth2_server.controller.authorization_server_metadata', AuthorizationServerMetadataController::class)
            ->args([
                service('router'),
                service('event_dispatcher'),
                null,
                [],
                [],
            ])
            ->tag('controller.service_arguments')
        ->alias(AuthorizationServerMetadataController::class, 'league.oauth2_server.controller.authorization_server_metadata')
    ;
};
