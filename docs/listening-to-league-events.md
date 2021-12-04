# Listening to League OAuth Server events

During the lifecycle of a request passing through the authorization server a number of events may be dispatched.
A list of those event names can be found in the constants of the `\League\OAuth2\Server\RequestEvent` class.

In order to listen to those events you need to create a standard Symfony event listener or subscriber for them.

Example:

1. Create the event listener.

    ```php
    <?php

    declare(strict_types=1);

    namespace App\EventListener;

    use League\OAuth2\Server\RequestAccessTokenEvent;

    final class FooListener
    {
        public function onAccessTokenIssuedEvent(RequestAccessTokenEvent $event): void
        {
            // do something
        }
    }
    ```

1. Register the event listener:

    ```yaml
    services:
        App\EventListener\FooListener:
            tags:
                - { name: kernel.event_listener, event: access_token.issued, method: onAccessTokenIssuedEvent }
    ```
