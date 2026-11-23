<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Controller\SecurityTestController;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\ScopedEventDispatcher;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(SecurityTestController::class)
            ->autoconfigure()
            ->autowire()
        ->set('logger', NullLogger::class)
    ;

    if (class_exists(ScopedEventDispatcher::class)) {
        $container->services()
            ->set(ScopedEventDispatcher::class)
                ->decorate('event_dispatcher')
                ->args([service('.inner')]);
    }
};
