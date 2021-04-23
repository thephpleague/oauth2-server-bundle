<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;

interface ClientManagerInterface
{
    public function save(AbstractClient $client): void;

    public function remove(AbstractClient $client): void;

    public function find(string $identifier): ?AbstractClient;

    /**
     * @return list<AbstractClient>
     */
    public function list(?ClientFilter $clientFilter): array;
}
