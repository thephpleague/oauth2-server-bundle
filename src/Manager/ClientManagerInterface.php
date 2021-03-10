<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\Client;

interface ClientManagerInterface
{
    public function save(Client $client): void;

    public function remove(Client $client): void;

    public function find(string $identifier): ?Client;

    /**
     * @return list<Client>
     */
    public function list(?ClientFilter $clientFilter): array;
}
