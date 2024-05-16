<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service;

use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use League\OAuth2\Server\RequestEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SymfonyLeagueEventListenerProvider implements ListenerSubscriber
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function subscribeListeners(ListenerRegistry $acceptor): void
    {
        $listener = \Closure::fromCallable([$this, 'dispatchLeagueEventWithSymfonyEventDispatcher']);

        $acceptor->subscribeTo(RequestEvent::class, $listener);
    }

    private function dispatchLeagueEventWithSymfonyEventDispatcher(RequestEvent $event): void
    {
        $this->eventDispatcher->dispatch($event, $event->eventName());
    }
}
