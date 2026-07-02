UPGRADE GUIDE
=============

FROM 1.2 to 2.0
---------------

 * Bump the minimum required versions to PHP 8.2+, Symfony 7.4+, Doctrine ORM 3.0+ / DBAL 4.0+ with DoctrineBundle 2.15+, and `lcobucci/jwt` 5.6+
 * Add method `getName` to `ClientInterface`
 * Change config option `authorization_server.enable_password_grant` default value to `false`
 * Change config option `authorization_server.enable_implicit_grant` default value to `false`
 * Require `EventDispatcherInterface` argument in `AccessTokenRepository::__construct`
 * The `client.allow_plaintext_secrets` option value is now ignored and plaintext client secrets are no longer supported
