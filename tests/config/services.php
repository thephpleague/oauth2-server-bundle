<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController;
use Psr\Log\NullLogger;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(SecurityTestController::class)
            ->autoconfigure()
            ->autowire()
        ->set('logger', NullLogger::class)
    ;
};
