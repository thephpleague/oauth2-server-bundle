UPGRADE GUIDE
=============

FROM 1.x to 2.0
---------------

 * Add method `getName` to `ClientInterface`
 * Change config option `authorization_server.enable_password_grant` default value to `false`
 * Change config option `authorization_server.enable_implicit_grant` default value to `false`


FROM 1.1 to 1.2
---------------

 * Deprecate not implementing method `ClientInterface::getName()`. This method will be required in 2.0
 * Deprecate not setting the `authorization_server.enable_password_grant` config option. It will default to `false` in 2.0
 * Deprecate not setting the `authorization_server.enable_implicit_grant` config option. It will default to `false` in 2.0
