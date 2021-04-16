<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use League\Bundle\OAuth2ServerBundle\Command\ClearExpiredTokensCommand;
use League\Bundle\OAuth2ServerBundle\Command\CreateClientCommand;
use League\Bundle\OAuth2ServerBundle\Command\DeleteClientCommand;
use League\Bundle\OAuth2ServerBundle\Command\ListClientsCommand;
use League\Bundle\OAuth2ServerBundle\Command\UpdateClientCommand;
use League\Bundle\OAuth2ServerBundle\Controller\AuthorizationController;
use League\Bundle\OAuth2ServerBundle\Controller\TokenController;
use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverter;
use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Converter\UserConverter;
use League\Bundle\OAuth2ServerBundle\Converter\UserConverterInterface;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEventFactory;
use League\Bundle\OAuth2ServerBundle\EventListener\AuthorizationRequestUserResolvingListener;
use League\Bundle\OAuth2ServerBundle\EventListener\ConvertExceptionToResponseListener;
use League\Bundle\OAuth2ServerBundle\League\AuthorizationServer\GrantConfigurator;
use League\Bundle\OAuth2ServerBundle\League\Repository\AccessTokenRepository;
use League\Bundle\OAuth2ServerBundle\League\Repository\AuthCodeRepository;
use League\Bundle\OAuth2ServerBundle\League\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\League\Repository\RefreshTokenRepository;
use League\Bundle\OAuth2ServerBundle\League\Repository\ScopeRepository;
use League\Bundle\OAuth2ServerBundle\League\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ScopeManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Provider\OAuth2Provider;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2TokenFactory;
use League\Bundle\OAuth2ServerBundle\Security\EntryPoint\OAuth2EntryPoint;
use League\Bundle\OAuth2ServerBundle\Security\Firewall\OAuth2Listener;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;

// BC Layer for < 5.1 versions
if (!function_exists('service')) {
    function service(string $id): ReferenceConfigurator
    {
        $fn = function_exists('Symfony\Component\DependencyInjection\Loader\Configurator\service')
            ? 'Symfony\Component\DependencyInjection\Loader\Configurator\service'
            : 'Symfony\Component\DependencyInjection\Loader\Configurator\ref';

        return ($fn)($id);
    }
}

return static function (ContainerConfigurator $container): void {
    $container->services()

        // League repositories
        ->set(ClientRepository::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
        ->alias(ClientRepositoryInterface::class, ClientRepository::class)

        ->set(AccessTokenRepository::class)
            ->args([
                service(AccessTokenManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverter::class),
            ])
        ->alias(AccessTokenRepositoryInterface::class, AccessTokenRepository::class)

        ->set(RefreshTokenRepository::class)
            ->args([
                service(RefreshTokenManagerInterface::class),
                service(AccessTokenManagerInterface::class),
            ])
        ->alias(RefreshTokenRepositoryInterface::class, RefreshTokenRepository::class)

        ->set(ScopeRepository::class)
            ->args([
                service(ScopeManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverter::class),
                service(EventDispatcherInterface::class),
            ])
        ->alias(ScopeRepositoryInterface::class, ScopeRepository::class)

        ->set(UserRepository::class)
            ->args([
                service(ClientManagerInterface::class),
                service(EventDispatcherInterface::class),
                service(UserConverter::class),
            ])
        ->alias(UserRepositoryInterface::class, UserRepository::class)

        ->set(AuthCodeRepository::class)
            ->args([
                service(AuthorizationCodeManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverter::class),
            ])
        ->alias(AuthCodeRepositoryInterface::class, AuthCodeRepository::class)

        // Security layer
        ->set(OAuth2Provider::class)
            ->args([
                service(UserProviderInterface::class),
                service(ResourceServer::class),
                service(OAuth2TokenFactory::class),
                null,
            ])

        ->set(OAuth2EntryPoint::class)

        ->set(OAuth2Listener::class)
            ->args([
                service(TokenStorageInterface::class),
                service(AuthenticationManagerInterface::class),
                service('league.oauth2-server.psr_http_factory'),
                service(OAuth2TokenFactory::class),
                null,
            ])

        ->set(GrantConfigurator::class)
            ->args([
                tagged_iterator('league.oauth2-server.authorization_server.grant'),
            ])

        // League authorization server
        ->set(AuthorizationServer::class)
            ->args([
                service(ClientRepositoryInterface::class),
                service(AccessTokenRepositoryInterface::class),
                service(ScopeRepositoryInterface::class),
                null,
                null,
            ])
            ->configurator(service(GrantConfigurator::class))
        ->alias('league.oauth2-server.authorization_server', AuthorizationServer::class)

        // League resource server
        ->set(ResourceServer::class)
            ->args([
                service(AccessTokenRepositoryInterface::class),
                null,
            ])
        ->alias('league.oauth2-server.resource_server', ResourceServer::class)

        // League authorization server grants
        ->set(ClientCredentialsGrant::class)
        ->alias('league.oauth2-server.client_credentials_grant', ClientCredentialsGrant::class)

        ->set(PasswordGrant::class)
            ->args([
                service(UserRepositoryInterface::class),
                service(RefreshTokenRepositoryInterface::class),
            ])
        ->alias('league.oauth2-server.password_grant', PasswordGrant::class)

        ->set(RefreshTokenGrant::class)
            ->args([
                service(RefreshTokenRepositoryInterface::class),
            ])
        ->alias('league.oauth2-server.refresh_token_grant', RefreshTokenGrant::class)

        ->set(AuthCodeGrant::class)
            ->args([
                service(AuthCodeRepositoryInterface::class),
                service(RefreshTokenRepositoryInterface::class),
                null,
            ])
        ->alias('league.oauth2-server.auth_code_grant', AuthCodeGrant::class)

        ->set(ImplicitGrant::class)
            ->args([
                null,
            ])
        ->alias('league.oauth2-server.implicit_grant', ImplicitGrant::class)

        // Authorization controller
        ->set(AuthorizationController::class)
            ->args([
                service(AuthorizationServer::class),
                service(EventDispatcherInterface::class),
                service(AuthorizationRequestResolveEventFactory::class),
                service(UserConverter::class),
                service(ClientManagerInterface::class),
                service('league.oauth2-server.psr_http_factory'),
                service('league.oauth2-server.http_foundation_factory'),
                service(Psr17Factory::class),
            ])
            ->tag('controller.service_arguments')

        // Authorization listeners
        ->set(AuthorizationRequestUserResolvingListener::class)
            ->args([
                service(Security::class),
            ])
            ->tag('kernel.event_listener', [
                'event' => 'league.oauth2-server.authorization_request_resolve',
                'method' => 'onAuthorizationRequest',
                'priority' => 1024,
            ])

        ->set(ConvertExceptionToResponseListener::class)

        // Token controller
        ->set(TokenController::class)
            ->args([
                service(AuthorizationServer::class),
                service('league.oauth2-server.psr_http_factory'),
                service('league.oauth2-server.http_foundation_factory'),
                service(Psr17Factory::class),
            ])
            ->tag('controller.service_arguments')

        // Commands
        ->set(CreateClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')

        ->set(UpdateClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')

        ->set(DeleteClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')

        ->set(ListClientsCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')

        ->set(ClearExpiredTokensCommand::class)
            ->args([
                service(AccessTokenManagerInterface::class),
                service(RefreshTokenManagerInterface::class),
                service(AuthorizationCodeManagerInterface::class),
            ])
            ->tag('console.command')

        // Utility services
        ->set(UserConverter::class)
        ->alias(UserConverterInterface::class, UserConverter::class)

        ->set(ScopeConverter::class)
        ->alias(ScopeConverterInterface::class, ScopeConverter::class)

        ->set(AuthorizationRequestResolveEventFactory::class)
            ->args([
                service(ScopeConverter::class),
                service(ClientManagerInterface::class),
            ])

        ->set(OAuth2TokenFactory::class)

        // Storage managers
        ->set(ScopeManager::class)
            ->args([
                null,
            ])
        ->alias(ScopeManagerInterface::class, ScopeManager::class)

        // PSR-7/17
        ->set(Psr17Factory::class)

        ->set('league.oauth2-server.psr_http_factory', PsrHttpFactory::class)
            ->args([
                service(Psr17Factory::class),
                service(Psr17Factory::class),
                service(Psr17Factory::class),
                service(Psr17Factory::class),
            ])

        ->set('league.oauth2-server.http_foundation_factory', HttpFoundationFactory::class)
    ;
};
