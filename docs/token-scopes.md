# Token scopes

## Setting default scopes

Having a client with no scope gives the client access to all the scopes.
In most cases, it's a bad idea and could result as security vulnerability.

That's why you have to specify in the bundle configuration the default scopes that will be applied when no scope is given:
```yaml
# config/packages/league_oauth2_server.yaml

league_oauth2_server:
    scopes:
        available: [EMAIL, PREFERENCES]
        default: [EMAIL]
```

If you still want clients without scopes to have access to every scopes, you can use role hierarchy as a workaround:
```yaml
# config/packages/league_oauth2_server.yaml

league_oauth2_server:
    role_prefix: ROLE_OAUTH2_

    scopes:
        available: [EMAIL, PREFERENCES, SUPER_USER]
        default: [SUPER_USER]
```

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_OAUTH2_SUPER_USER: [ROLE_OAUTH2_EMAIL, ROLE_OAUTH2_PREFERENCES]
```

## Controlling token scopes

It's possible to alter issued access token's scopes by subscribing to the `league.oauth2_server.event.scope_resolve` event.

### Example

#### Listener
```php
<?php

namespace App\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\ScopeResolveEvent;

final class ScopeResolveListener
{
    public function onScopeResolve(ScopeResolveEvent $event): void
    {
        $requestedScopes = $event->getScopes();

        // ...Make adjustments to the client's requested scopes...

        $event->setScopes(...$requestedScopes);
    }
}
```

#### Service configuration

```yaml
App\EventListener\ScopeResolveListener:
    tags:
        - { name: kernel.event_listener, event: league.oauth2_server.event.scope_resolve, method: onScopeResolve }
```
