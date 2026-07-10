<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'firewalls' => [
            'test' => [
                'provider' => 'in_memory',
                'pattern' => '^/security-test',
                'stateless' => true,
                'oauth2' => true,
            ],
            'authorization' => [
                'provider' => 'in_memory',
                'pattern' => '^/authorize',
                'http_basic' => true,
                'stateless' => true,
            ],
        ],
        'providers' => [
            'in_memory' => [
                'memory' => [
                    'users' => [
                        FixtureFactory::FIXTURE_USER => [
                            'roles' => ['ROLE_USER'],
                        ],
                        FixtureFactory::FIXTURE_USER_TWO => [
                            'roles' => ['ROLE_USER'],
                        ],
                    ],
                ],
            ],
            'another_provider' => [
                'memory' => [
                    'users' => [
                        FixtureFactory::FIXTURE_USER => [
                            'roles' => ['ROLE_USER'],
                        ],
                        FixtureFactory::FIXTURE_USER_TWO => [
                            'roles' => ['ROLE_USER'],
                        ],
                    ],
                ],
            ],
        ],
        'access_control' => [
            [
                'path' => '^/authorize',
                'roles' => 'IS_AUTHENTICATED',
            ],
            [
                'path' => '^/device-code',
                'roles' => 'IS_AUTHENTICATED',
            ],
        ],
    ]);
};
