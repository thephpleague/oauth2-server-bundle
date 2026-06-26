<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeAuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeClientManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeRefreshTokenManager;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('test.access_token_manager', FakeAccessTokenManager::class)
        ->set('test.authorization_code_manager', FakeAuthorizationCodeManager::class)
        ->set('test.client_manager', FakeClientManager::class)
        ->set('test.refresh_token_manager', FakeRefreshTokenManager::class)
        ->set('test.credentials_revoker', FakeCredentialsRevoker::class)
        ->set('test.device_code_manager', FakeDeviceCodeManager::class)
    ;
};
