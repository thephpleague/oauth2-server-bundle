# Authorization server metadata

Clients can discover the endpoints and capabilities of the authorization server through the metadata document of [RFC 8414](https://datatracker.ietf.org/doc/html/rfc8414). MCP clients and other generic OAuth clients rely on it.

The endpoint is off by default. Enabling it takes two steps.

1. Enable it in the bundle configuration and set the issuer identifier:

    ```yaml
    league_oauth2_server:
        authorization_server:
            metadata:
                enabled: true
                issuer: 'https://auth.example.com'
    ```

    The issuer is an `https` URL without query or fragment. It may carry a path. For local development, `http` is accepted for `localhost`, `127.0.0.1` and `[::1]`.

    The issuer must be a literal. The path of the route is built from it when the container is compiled, so a parameter or an environment variable cannot be used. To use another issuer per environment, set it in `config/packages/<env>/league_oauth2_server.yaml` or with `when@<env>`.

1. Import the route:

    ```yaml
    # config/routes/league_oauth2_server.yaml
    league_oauth2_server_metadata:
        resource: '@LeagueOAuth2ServerBundle/config/routes_metadata.php'
    ```

    RFC 8414 section 3.1 puts the well-known segment between the host and the path of the issuer. The route follows the issuer you configured:

    | Issuer                           | Metadata URL                                                         |
    |----------------------------------|----------------------------------------------------------------------|
    | `https://auth.example.com`       | `https://auth.example.com/.well-known/oauth-authorization-server`    |
    | `https://example.com/auth`       | `https://example.com/.well-known/oauth-authorization-server/auth`    |

    Import it without a prefix. A prefix or a locale group would move the well-known path away from where clients look for it. While the metadata are not enabled, the route answers `404`.

## Content

The document is built from the configuration of the bundle.

| Field                                   | Value                                                                                                  |
|-----------------------------------------|--------------------------------------------------------------------------------------------------------|
| `issuer`                                | The configured issuer.                                                                                 |
| `authorization_endpoint`                | The `oauth2_authorize` route, when the authorization code or the implicit grant is enabled.            |
| `token_endpoint`                        | The `oauth2_token` route, when any grant other than the implicit grant is enabled.                     |
| `device_authorization_endpoint`         | The `oauth2_device_code` route, when the device code grant is enabled.                                 |
| `response_types_supported`              | `code` and `token`, following the authorization code and the implicit grant.                           |
| `grant_types_supported`                 | The enabled grants.                                                                                    |
| `code_challenge_methods_supported`      | `S256`, when the authorization code grant is enabled.                                                  |
| `token_endpoint_auth_methods_supported` | `client_secret_basic` and `client_secret_post`, plus `none` when a grant that accepts public clients is enabled (every grant except client credentials and implicit). Left out without a token endpoint. |
| `scopes_supported`                      | `scopes.available`.                                                                                    |

The endpoints are built from the routes of the bundle on the scheme, host and port of the issuer. A host or scheme requirement on those routes is kept.

An endpoint whose route you did not import is left out, and so is one whose route needs parameters, for example because you imported the routes under a prefix with a placeholder. The document is then not compliant with RFC 8414 section 2, which asks for the `authorization_endpoint` and the `token_endpoint` of the grants that use them. Import the routes of the bundle, or add the endpoints in a listener of the event below.

`grant_types_supported` lists the grants the bundle knows. Grants you register with the `league.oauth2_server.authorization_server.grant` tag are not part of it. Add them in the event listener. The same goes for trimming `scopes_supported`, which lists every available scope.

## CORS

The endpoint sets no CORS headers and answers `OPTIONS` with `405`, like the other endpoints of the bundle. Browser based clients cannot read the document across origins without them. Add the headers for the well-known path with a CORS bundle such as NelmioCorsBundle.

## Changing the document

Listen to the `OAuth2Events::AUTHORIZATION_SERVER_METADATA` event to add or change fields:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationServerMetadataEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OAuth2Events::AUTHORIZATION_SERVER_METADATA)]
final class MetadataListener
{
    public function __invoke(AuthorizationServerMetadataEvent $event): void
    {
        $event->setMetadata($event->getMetadata() + [
            'service_documentation' => 'https://example.com/docs',
        ]);
    }
}
```
