<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantNullAccessTokenTTL;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrantUndefinedAccessTokenTTL;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(FakeGrant::class)
            ->tag('league.oauth2_server.authorization_server.grant', ['accessTokenTTL' => 'PT5H'])
            ->tag('league.oauth2_server.authorization_server.grant', ['accessTokenTTL' => 'PT3H'])

        ->set(FakeGrantNullAccessTokenTTL::class)
            ->tag('league.oauth2_server.authorization_server.grant', ['accessTokenTTL' => null])

        ->set(FakeGrantUndefinedAccessTokenTTL::class)
            ->tag('league.oauth2_server.authorization_server.grant')
    ;
};
