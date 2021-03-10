<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class ClientManager implements ClientManagerInterface
{
    /**
     * @var array<string, Client>
     */
    private $clients = [];

    public function find(string $identifier): ?Client
    {
        return $this->clients[$identifier] ?? null;
    }

    public function save(Client $client): void
    {
        $this->clients[$client->getIdentifier()] = $client;
    }

    public function remove(Client $client): void
    {
        unset($this->clients[$client->getIdentifier()]);
    }

    /**
     * @return list<Client>
     */
    public function list(?ClientFilter $clientFilter): array
    {
        if (null === $clientFilter || !$clientFilter->hasFilters()) {
            return \array_values($this->clients);
        }

        return \array_values(array_filter($this->clients, static function (Client $client) use ($clientFilter): bool {
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

        /** @var list<string> $clientValues */
        $clientValues = array_map('strval', $clientValues);
        /** @var list<string> $filterValues */
        $filterValues = array_map('strval', $filterValues);

        /** @var list<string> $valuesPassed */
        $valuesPassed = array_intersect($filterValues, $clientValues);

        return \count($valuesPassed) > 0;
    }
}
