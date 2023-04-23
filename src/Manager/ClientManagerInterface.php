<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;

interface ClientManagerInterface
{
    public function save(ClientInterface $client): void;

    public function remove(ClientInterface $client): void;

    public function find(string $identifier): ?ClientInterface;

    /**
     * @return list<ClientInterface>
     */
    public function list(?ClientFilter $clientFilter): array;
}
