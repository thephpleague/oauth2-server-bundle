<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantConfigurator;
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
use League\Bundle\OAuth2ServerBundle\EventListener\AddClientDefaultScopesListener;
use League\Bundle\OAuth2ServerBundle\EventListener\AuthorizationRequestUserResolvingListener;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ScopeManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\Repository\AuthCodeRepository;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use League\Bundle\OAuth2ServerBundle\Repository\RefreshTokenRepository;
use League\Bundle\OAuth2ServerBundle\Repository\ScopeRepository;
use League\Bundle\OAuth2ServerBundle\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator;
use League\Bundle\OAuth2ServerBundle\Security\EventListener\CheckScopeListener;
use League\Bundle\OAuth2ServerBundle\Service\SymfonyLeagueEventListenerProvider;
use League\Event\Emitter;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

return static function (ContainerConfigurator $container): void {
    $container->services()

        // League repositories
        ->set('league.oauth2_server.repository.client', ClientRepository::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
        ->alias(ClientRepositoryInterface::class, 'league.oauth2_server.repository.client')
        ->alias(ClientRepository::class, 'league.oauth2_server.repository.client')

        ->set('league.oauth2_server.repository.refresh_token', RefreshTokenRepository::class)
            ->args([
                service(RefreshTokenManagerInterface::class),
                service(AccessTokenManagerInterface::class),
            ])
        ->alias(RefreshTokenRepositoryInterface::class, 'league.oauth2_server.repository.refresh_token')
        ->alias(RefreshTokenRepository::class, 'league.oauth2_server.repository.refresh_token')

        ->set('league.oauth2_server.repository.scope', ScopeRepository::class)
            ->args([
                service(ScopeManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverterInterface::class),
                service(EventDispatcherInterface::class),
            ])
        ->alias(ScopeRepositoryInterface::class, 'league.oauth2_server.repository.scope')
        ->alias(ScopeRepository::class, 'league.oauth2_server.repository.scope')

        ->set('league.oauth2_server.repository.user', UserRepository::class)
            ->args([
                service(ClientManagerInterface::class),
                service(EventDispatcherInterface::class),
                service(UserConverterInterface::class),
            ])
        ->alias(UserRepositoryInterface::class, 'league.oauth2_server.repository.user')
        ->alias(UserRepository::class, 'league.oauth2_server.repository.user')

        ->set('league.oauth2_server.repository.auth_code', AuthCodeRepository::class)
            ->args([
                service(AuthorizationCodeManagerInterface::class),
                service(ClientManagerInterface::class),
                service(ScopeConverterInterface::class),
            ])
        ->alias(AuthCodeRepositoryInterface::class, 'league.oauth2_server.repository.auth_code')
        ->alias(AuthCodeRepository::class, 'league.oauth2_server.repository.auth_code')

        // Security layer
        ->set('league.oauth2_server.authenticator.oauth2', OAuth2Authenticator::class)
            ->args([
                service('league.oauth2_server.factory.psr_http'),
                service(ResourceServer::class),
                abstract_arg('User Provider'),
                abstract_arg('Role prefix'),
            ])
        ->alias(OAuth2Authenticator::class, 'league.oauth2_server.authenticator.oauth2')

        ->set('league.oauth2_server.listener.check_scope', CheckScopeListener::class)
            ->args([
                service(RequestStack::class),
            ])
            ->tag('kernel.event_subscriber')
        ->alias(CheckScopeListener::class, 'league.oauth2_server.listener.check_scope')

        ->set('league.oauth2_server.symfony_league_listener_provider', SymfonyLeagueEventListenerProvider::class)
        ->args([
            service('event_dispatcher'),
        ])
        ->alias(SymfonyLeagueEventListenerProvider::class, 'league.oauth2_server.symfony_league_listener_provider')

        ->set('league.oauth2_server.emitter', Emitter::class)
        ->call('useListenerProvider', [service('league.oauth2_server.symfony_league_listener_provider')])

        ->set('league.oauth2_server.authorization_server.grant_configurator', GrantConfigurator::class)
            ->args([
                tagged_iterator('league.oauth2_server.authorization_server.grant'),
            ])
        ->alias(GrantConfigurator::class, 'league.oauth2_server.authorization_server.grant_configurator')

        // League authorization server
        ->set('league.oauth2_server.authorization_server', AuthorizationServer::class)
            ->args([
                service(ClientRepositoryInterface::class),
                service(AccessTokenRepositoryInterface::class),
                service(ScopeRepositoryInterface::class),
                null,
                null,
            ])
            ->call('setEmitter', [service('league.oauth2_server.emitter')])
            ->configurator(service(GrantConfigurator::class))
        ->alias(AuthorizationServer::class, 'league.oauth2_server.authorization_server')

        // League resource server
        ->set('league.oauth2_server.resource_server', ResourceServer::class)
            ->args([
                service(AccessTokenRepositoryInterface::class),
                null,
            ])
        ->alias(ResourceServer::class, 'league.oauth2_server.resource_server')

        // League authorization server grants
        ->set('league.oauth2_server.grant.client_credentials', ClientCredentialsGrant::class)
        ->alias(ClientCredentialsGrant::class, 'league.oauth2_server.grant.client_credentials')

        ->set('league.oauth2_server.grant.password', PasswordGrant::class)
            ->args([
                service(UserRepositoryInterface::class),
                service(RefreshTokenRepositoryInterface::class),
            ])
        ->alias(PasswordGrant::class, 'league.oauth2_server.grant.password')

        ->set('league.oauth2_server.grant.refresh_token', RefreshTokenGrant::class)
            ->args([
                service(RefreshTokenRepositoryInterface::class),
            ])
        ->alias(RefreshTokenGrant::class, 'league.oauth2_server.grant.refresh_token')

        ->set('league.oauth2_server.grant.auth_code', AuthCodeGrant::class)
            ->args([
                service(AuthCodeRepositoryInterface::class),
                service(RefreshTokenRepositoryInterface::class),
                null,
            ])
        ->alias(AuthCodeGrant::class, 'league.oauth2_server.grant.auth_code')

        ->set('league.oauth2_server.grant.implicit', ImplicitGrant::class)
            ->args([
                null,
            ])
        ->alias(ImplicitGrant::class, 'league.oauth2_server.grant.implicit')

        // Authorization controller
        ->set('league.oauth2_server.controller.authorization', AuthorizationController::class)
            ->args([
                service(AuthorizationServer::class),
                service(EventDispatcherInterface::class),
                service(AuthorizationRequestResolveEventFactory::class),
                service(UserConverterInterface::class),
                service(ClientManagerInterface::class),
                service('league.oauth2_server.factory.psr_http'),
                service('league.oauth2_server.factory.http_foundation'),
                service('league.oauth2_server.factory.psr17'),
            ])
            ->tag('controller.service_arguments')
        ->alias(AuthorizationController::class, 'league.oauth2_server.controller.authorization')

        // Authorization listeners
        ->set('league.oauth2_server.listener.authorization_request_user_resolving', AuthorizationRequestUserResolvingListener::class)
            ->args([
                service(Security::class),
            ])
            ->tag('kernel.event_listener', [
                'event' => OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE,
                'method' => 'onAuthorizationRequest',
                'priority' => 1024,
            ])
        ->alias(AuthorizationRequestUserResolvingListener::class, 'league.oauth2_server.listener.authorization_request_user_resolving')

        // Token controller
        ->set('league.oauth2_server.controller.token', TokenController::class)
            ->args([
                service(AuthorizationServer::class),
                service('league.oauth2_server.factory.psr_http'),
                service('league.oauth2_server.factory.http_foundation'),
                service('league.oauth2_server.factory.psr17'),
                service('event_dispatcher'),
            ])
            ->tag('controller.service_arguments')
        ->alias(TokenController::class, 'league.oauth2_server.controller.token')

        // Commands
        ->set('league.oauth2_server.command.create_client', CreateClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
                null,
            ])
            ->tag('console.command')
        ->alias(CreateClientCommand::class, 'league.oauth2_server.command.create_client')

        ->set('league.oauth2_server.command.update_client', UpdateClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')
        ->alias(UpdateClientCommand::class, 'league.oauth2_server.command.update_client')

        ->set('league.oauth2_server.command.delete_client', DeleteClientCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')
        ->alias(DeleteClientCommand::class, 'league.oauth2_server.command.delete_client')

        ->set('league.oauth2_server.command.list_clients', ListClientsCommand::class)
            ->args([
                service(ClientManagerInterface::class),
            ])
            ->tag('console.command')
        ->alias(ListClientsCommand::class, 'league.oauth2_server.command.list_clients')

        ->set('league.oauth2_server.command.clear_expired_tokens', ClearExpiredTokensCommand::class)
            ->args([
                service(AccessTokenManagerInterface::class),
                service(RefreshTokenManagerInterface::class),
                service(AuthorizationCodeManagerInterface::class),
            ])
            ->tag('console.command')
        ->alias(ClearExpiredTokensCommand::class, 'league.oauth2_server.command.clear_expired_tokens')

        // Utility services
        ->set('league.oauth2_server.converter.user', UserConverter::class)
        ->alias(UserConverterInterface::class, 'league.oauth2_server.converter.user')
        ->alias(UserConverter::class, 'league.oauth2_server.converter.user')

        ->set('league.oauth2_server.converter.scope', ScopeConverter::class)
        ->alias(ScopeConverterInterface::class, 'league.oauth2_server.converter.scope')
        ->alias(ScopeConverter::class, 'league.oauth2_server.converter.scope')

        ->set('league.oauth2_server.factory.authorization_request_resolve_event', AuthorizationRequestResolveEventFactory::class)
            ->args([
                service(ScopeConverterInterface::class),
                service(ClientManagerInterface::class),
            ])
        ->alias(AuthorizationRequestResolveEventFactory::class, 'league.oauth2_server.factory.authorization_request_resolve_event')

        // Listeners
        ->set(AddClientDefaultScopesListener::class)
            ->args([
                param('league.oauth2_server.scopes.default'),
            ])
            ->tag('kernel.event_listener', ['event' => OAuth2Events::PRE_SAVE_CLIENT])

        // Storage managers
        ->set('league.oauth2_server.manager.in_memory.scope', ScopeManager::class)
            ->args([
                null,
            ])
        ->alias(ScopeManagerInterface::class, 'league.oauth2_server.manager.in_memory.scope')
        ->alias(ScopeManager::class, 'league.oauth2_server.manager.in_memory.scope')

        // PSR-7/17
        ->set('league.oauth2_server.factory.psr17', Psr17Factory::class)

        ->set('league.oauth2_server.factory.psr_http', PsrHttpFactory::class)
            ->args([
                service('league.oauth2_server.factory.psr17'),
                service('league.oauth2_server.factory.psr17'),
                service('league.oauth2_server.factory.psr17'),
                service('league.oauth2_server.factory.psr17'),
            ])

        ->set('league.oauth2_server.factory.http_foundation', HttpFoundationFactory::class)
    ;
};
