<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'driver' => 'pdo_sqlite',
            'charset' => 'utf8mb4',
            'url' => 'sqlite:///:memory:',
            'default_table_options' => [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
            ],
        ],
        'orm' => [],
    ]);
};
