<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;

return static function (ContainerConfigurator $container): void {
    $container->extension('league_oauth2_server', [
        'authorization_server' => [
            'private_key' => TestHelper::PRIVATE_KEY_PATH,
            'encryption_key' => TestHelper::ENCRYPTION_KEY,
            'enable_password_grant' => true,
            'enable_implicit_grant' => true,
            'access_token_ttl' => 'PT2H',
        ],
        'resource_server' => ['public_key' => TestHelper::PUBLIC_KEY_PATH],
        'scopes' => [
            'available' => [
                FixtureFactory::FIXTURE_SCOPE_FIRST,
                FixtureFactory::FIXTURE_SCOPE_SECOND,
            ],
            'default' => [
                FixtureFactory::FIXTURE_SCOPE_SECOND,
            ],
        ],
        'persistence' => [
            'doctrine' => [
                'entity_manager' => 'default',
            ],
        ],
        'client' => [
            'allow_plaintext_secrets' => false,
        ],
    ]);
};
