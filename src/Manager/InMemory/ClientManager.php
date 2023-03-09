<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManager implements ClientManagerInterface
{
    /**
     * @var array<string, ClientInterface>
     */
    private $clients = [];

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function find(string $identifier): ?ClientInterface
    {
        return $this->clients[$identifier] ?? null;
    }

    public function save(ClientInterface $client): void
    {
        $event = $this->dispatcher->dispatch(new PreSaveClientEvent($client), OAuth2Events::PRE_SAVE_CLIENT);
        $client = $event->getClient();

        $this->clients[$client->getIdentifier()] = $client;
    }

    public function remove(ClientInterface $client): void
    {
        unset($this->clients[$client->getIdentifier()]);
    }

    /**
     * @return list<ClientInterface>
     */
    public function list(?ClientFilter $clientFilter): array
    {
        if (null === $clientFilter || !$clientFilter->hasFilters()) {
            return array_values($this->clients);
        }

        return array_values(array_filter($this->clients, static function (ClientInterface $client) use ($clientFilter): bool {
            if (!self::passesFilter($client->getGrants(), $clientFilter->getGrants())) {
                return false;
            }

            if (!self::passesFilter($client->getScopes(), $clientFilter->getScopes())) {
                return false;
            }

            if (!self::passesFilter($client->getRedirectUris(), $client->getRedirectUris())) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param list<RedirectUri|Grant|Scope> $clientValues
     * @param list<RedirectUri|Grant|Scope> $filterValues
     */
    private static function passesFilter(array $clientValues, array $filterValues): bool
    {
        if (empty($filterValues)) {
            return true;
        }

        $valuesPassed = array_intersect(
            array_map('strval', $filterValues),
            array_map('strval', $clientValues)
        );

        return \count($valuesPassed) > 0;
    }
}
