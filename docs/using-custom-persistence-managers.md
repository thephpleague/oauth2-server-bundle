# Using custom persistence managers

Implement the 4 interfaces from the `League\Bundle\OAuth2ServerBundle\Manager` namespace:
- [AccessTokenManagerInterface](../src/Manager/AccessTokenManagerInterface.php)
- [AuthorizationCodeManagerInterface](../src/Manager/AuthorizationCodeManagerInterface.php)
- [ClientManagerInterface](../src/Manager/ClientManagerInterface.php)
- [RefreshTokenManagerInterface](../src/Manager/RefreshTokenManagerInterface.php)
And the interface for `CredentialsRevokerInterface`:
- [CredentialsRevokerInterface](../src/Service/CredentialsRevokerInterface.php)

```php

Example:

```php
class MyAccessTokenManager implements AccessTokenManagerInterface
{
}

class MyAuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
}

class MyClientManager implements ClientManagerInterface
{
}

class MyRefreshTokenManager implements RefreshTokenManagerInterface
{
}

class MyCredentialsRevoker implements CredentialsRevokerInterface
{
}
```

Then register the services in the container:

```yaml
services:
    _defaults:
        autoconfigure: true

    App\Manager\MyAccessTokenManager: ~
    App\Manager\MyAuthorizationCodeManager: ~
    App\Manager\MyClientManager: ~
    App\Manager\MyRefreshTokenManager: ~
    App\Service\MyCredentialsRevoker: ~
```

Finally, configure the bundle to use the new managers:

```yaml
league_oauth2_server:
    persistence:
        custom:
            access_token_manager: App\Manager\MyAccessTokenManager
            authorization_code_manager: App\Manager\MyAuthorizationCodeManager
            client_manager: App\Manager\MyClientManager
            refresh_token_manager: App\Manager\MyRefreshTokenManager
            credentials_revoker: App\Service\MyCredentialsRevoker
```

## Optional

Example MySql table schema for custom persistence managers implementation:
```sql
CREATE TABLE `oauth2_access_token` (
  `identifier` char(80) NOT NULL,
  `client` varchar(32) NOT NULL,
  `expiry` datetime NOT NULL,
  `userIdentifier` varchar(128) DEFAULT NULL,
  `scopes` text,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `client` (`client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `oauth2_authorization_code` (
  `identifier` char(80) NOT NULL,
  `client` varchar(32) NOT NULL,
  `expiry` datetime NOT NULL,
  `userIdentifier` varchar(128) DEFAULT NULL,
  `scopes` text,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `client` (`client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `oauth2_client` (
  `identifier` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `secret` varchar(128) DEFAULT NULL,
  `redirectUris` text,
  `grants` text,
  `scopes` text,
  `active` tinyint(1) NOT NULL,
  `allowPlainTextPkce` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `oauth2_refresh_token` (
  `identifier` char(80) NOT NULL,
  `access_token` char(80) DEFAULT NULL,
  `expiry` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `access_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
