# Basic setup

## Managing clients

There are several commands available to manage clients.

### Add a client

To add a client you should use the `league:oauth2-server:create-client` command.

```sh
Description:
  Creates a new oAuth2 client

Usage:
  league:oauth2-server:create-client [options] [--] [<identifier> [<secret>]]

Arguments:
  name                               The client name
  identifier                         The client identifier
  secret                             The client secret

Options:
      --redirect-uri=REDIRECT-URI    Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs. (multiple values allowed)
      --grant-type=GRANT-TYPE        Sets allowed grant type for client. Use this option multiple times to set multiple grant types. (multiple values allowed)
      --scope=SCOPE                  Sets allowed scope for client. Use this option multiple times to set multiple scopes. (multiple values allowed)
      --public                       Creates a public client (a client which does not have a secret)
      --allow-plain-text-pkce        Creates a client which is allowed to create an authorization code grant PKCE request with the "plain" code challenge method
```


### Update a client

To update a client you should use the `league:oauth2-server:update-client` command.

```sh
Description:
  Updates an oAuth2 client

Usage:
  league:oauth2-server:update-client [options] [--] <identifier>

Arguments:
  identifier                                     The client identifier

Options:
      --add-redirect-uri=ADD-REDIRECT-URI        Add allowed redirect uri to the client. (multiple values allowed)
      --remove-redirect-uri=REMOVE-REDIRECT-URI  Remove allowed redirect uri to the client. (multiple values allowed)
      --add-grant-type=ADD-GRANT-TYPE            Add allowed grant type to the client. (multiple values allowed)
      --remove-grant-type=REMOVE-GRANT-TYPE      Remove allowed grant type to the client. (multiple values allowed)
      --add-scope=ADD-SCOPE                      Add allowed scope to the client. (multiple values allowed)
      --remove-scope=REMOVE-SCOPE                Remove allowed scope to the client. (multiple values allowed)
      --activate                                 Activate the client.
      --deactivate                               Deactivate the client.
```

### Delete a client
To delete a client you should use the `league:oauth2-server:delete-client` command.

```sh
Description:
  Deletes an oAuth2 client

Usage:
  league:oauth2-server:delete-client <identifier>

Arguments:
  identifier            The client ID
```

### List clients
To list clients you should use the `league:oauth2-server:list-clients` command.

```sh
Description:
  Lists existing oAuth2 clients

Usage:
  league:oauth2-server:list-clients [options]

Options:
      --columns=COLUMNS              Determine which columns are shown. Comma separated list. [default: "identifier, secret, scope, redirect uri, grant type"]
      --redirect-uri=REDIRECT-URI    Finds by redirect uri for client. Use this option multiple times to filter by multiple redirect URIs. (multiple values allowed)
      --grant-type=GRANT-TYPE        Finds by allowed grant type for client. Use this option multiple times to filter by multiple grant types. (multiple values allowed)
      --scope=SCOPE                  Finds by allowed scope for client. Use this option multiple times to find by multiple scopes. (multiple values allowed)__
```

## Configuring the Security layer

Add two new firewalls in your security configuration and enable the authenticator security system:

```yaml
security:
    enable_authenticator_manager: true

    firewalls:
        api_token:
            pattern: ^/api/token$
            security: false
        api:
            pattern: ^/api
            security: true
            stateless: true
            oauth2: true
```

* The `api_token` firewall will ensure that anyone can access the `/api/token` endpoint in order to be able to retrieve their access tokens.
* The `api` firewall will protect all routes prefixed with `/api` and clients will require a valid access token in order to access them.

Basically, any firewall which sets the `oauth2` parameter to `true` will make any routes that match the selected pattern go through our OAuth 2.0 security layer.

> **NOTE:** The order of firewalls is important because Symfony will evaluate them in the specified order.

## Restricting routes by scope

You can define the `oauth2_scopes` parameter on the route you which to restrict the access to. The user will have to authenticate with **all** scopes which you defined:

```yaml
oauth2_restricted:
    path: /api/restricted
    controller: 'App\Controller\FooController::barAction'
    defaults:
        oauth2_scopes: ['foo', 'bar']
```

## Security roles

Once the user gets past the `oauth2` firewall, they will be granted additional roles based on their granted [token scopes](token-scopes.md).
By default, the roles are named in the following format:

```
ROLE_OAUTH2_<scope>
```

Here's one of the example uses cases featuring the [@IsGranted](https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html#isgranted) annotation:

```php
/**
 * @IsGranted("ROLE_OAUTH2_EDIT")
 */
public function indexAction()
{
    // ...
}
```

> **NOTE:** You can change the `ROLE_OAUTH2_` prefix via the `role_prefix` configuration option described in [Installation section](../README.md#installation)

## Auth

There are two possible reasons for the authentication server to reject a request:
- Provided token is expired or invalid (HTTP response 401 `Unauthorized`)
- Provided token is valid but scopes are insufficient (HTTP response 403 `Forbidden`)

## Clearing expired access, refresh tokens and auth codes

To clear expired access and refresh tokens and auth codes you can use the `league:oauth2-server:clear-expired-tokens` command.

The command removes all tokens whose expiry time is lesser than the current.

```sh
Description:
  Clears all expired access and/or refresh tokens and/or auth codes

Usage:
  league:oauth2-server:clear-expired-tokens [options]

Options:
  -a, --access-tokens   Clear expired access tokens.
  -r, --refresh-tokens  Clear expired refresh tokens.
  -c, --auth-codes  Clear expired auth codes.
```

Not passing any option means that both expired access and refresh tokens as well as expired auth codes
will be cleared.

## CORS requests

For CORS handling, use [NelmioCorsBundle](https://github.com/nelmio/NelmioCorsBundle)
