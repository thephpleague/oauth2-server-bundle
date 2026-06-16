UPGRADE GUIDE
=============

FROM 1.x to 2.0
---------------

 * Add method `getName` to `ClientInterface`
 * Change config option `authorization_server.enable_password_grant` default value to `false`
 * Change config option `authorization_server.enable_implicit_grant` default value to `false`
 * Add `EventDispatcherInterface` argument to `AccessTokenRepository::__construct()`
 * The `client.allow_plaintext_secrets` option value is now ignored and plaintext client secrets are no longer supported
 * `League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface` is deprecated, use `League\OAuth2\Server\Grant\GrantTypeInterface` with `accessTokenTTL` tag attribute instead


FROM 1.1 to 1.2
---------------

 * Deprecate not implementing method `ClientInterface::getName()`. This method will be required in 2.0
 * Deprecate not setting the `authorization_server.enable_password_grant` config option. It will default to `false` in 2.0
 * Deprecate not setting the `authorization_server.enable_implicit_grant` config option. It will default to `false` in 2.0
 * Deprecate not passing an `EventDispatcherInterface` instance to `AccessTokenRepository::__construct()`
 * Client secrets are now stored hashed. The client `secret` column length was increased from 128 to 255 to fit the hashed value; with the Doctrine persistence, generate and run a migration to apply this schema change before rehashing your client secrets
 * Add option `client.allow_plaintext_secrets` (default `true`) controlling whether plaintext client secrets are allowed. Setting it to `true` is deprecated: existing plaintext secrets keep working and are rehashed transparently on first successful authentication, but you should run the `league:oauth2-server:rehash-client-secrets` command to rehash them all and then set this option to `false`. The option value will be ignored in 2.0
