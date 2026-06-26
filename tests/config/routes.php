<?php

declare(strict_types=1);

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes
        ->add('security_test', '/security-test')
            ->controller([SecurityTestController::class, 'helloAction'])

        ->add('security_test_scopes', '/security-test-scopes')
            ->controller([SecurityTestController::class, 'scopeAction'])
            ->defaults([
                'oauth2_scopes' => [FixtureFactory::FIXTURE_SCOPE_FIRST],
            ])

        ->add('security_test_roles', '/security-test-roles')
            ->controller([SecurityTestController::class, 'rolesAction'])
            ->defaults([
                'oauth2_scopes' => [FixtureFactory::FIXTURE_SCOPE_FIRST],
            ])

        ->add('security_test_authorization', '/security-test-authorization')
            ->controller([SecurityTestController::class, 'authorizationAction'])
    ;
};
