<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager\FakeAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager\FakeAuthorizationCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager\FakeClientManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager\FakeDeviceCodeManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager\FakeRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Service\FakeCredentialsRevoker;

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
