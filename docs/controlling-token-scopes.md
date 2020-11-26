# Controlling token scopes

It's possible to alter issued access token's scopes by subscribing to the `league.oauth2-server.scope_resolve` event.

## Example

### Listener
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

### Service configuration

```yaml
App\EventListener\ScopeResolveListener:
    tags:
        - { name: kernel.event_listener, event: league.oauth2-server.scope_resolve, method: onScopeResolve }
```
