UPGRADE GUIDE
=============

FROM 1.x to 2.0
---------------

 * Add method `getName` to `ClientInterface`
 * Change config option `authorization_server.enable_password_grant` default value to `false`
 * Change config option `authorization_server.enable_implicit_grant` default value to `false`
 * Require `EventDispatcherInterface` argument in `AccessTokenRepository::__construct`
 * The `client.allow_plaintext_secrets` option value is now ignored and plaintext client secrets are no longer supported
 * Add method `setSecret` to `ClientInterface`
 * Require `PasswordHasherInterface` argument in `CreateClientCommand::__construct()`
 * Require `PasswordHasherInterface` argument in `ClientRepository::__construct()`
 * Interface `AuthorizationServer\GrantTypeInterface` has been removed
 * Service `league.oauth2_server.authorization_server.grant_configurator` has been removed
 * Service alias `League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantConfigurator` has been removed
