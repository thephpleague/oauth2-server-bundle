# Password grant handling

The `password` grant issues access and refresh tokens that are bound to both a client and a user within your application. As user system implementations can differ greatly on an application basis, the `league.oauth2_server.event.user_resolve` was created which allows you to decide which user you want to bind to issuing tokens.

## Requirements

The user model should implement the `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface` interface.

## Example

### Listener

```php
<?php

namespace App\EventListener;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;

final class UserResolveListener
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;

    public function __construct(UserProviderInterface $userProvider, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userProvider = $userProvider;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function onUserResolve(UserResolveEvent $event): void
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($event->getUsername());
        } catch (AuthenticationException $e) {
            return;
        }

        if (null === $user || !($user instanceof PasswordAuthenticatedUserInterface)) {
            return;
        }

        if (!$this->userPasswordHasher->isPasswordValid($user, $event->getPassword())) {
            return;
        }

        $event->setUser($user);
    }
}
```

### Service configuration

```yaml
App\EventListener\UserResolveListener:
    arguments:
        - '@security.user_providers'
        - '@security.password_hasher'
    tags:
        - { name: kernel.event_listener, event: league.oauth2_server.event.user_resolve, method: onUserResolve }
```
