<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;

class FakeClientManager implements ClientManagerInterface
{
    public function save(ClientInterface $client): void
    {
    }

    public function remove(ClientInterface $client): void
    {
    }

    public function find(string $identifier): ?ClientInterface
    {
        return null;
    }

    public function list(?ClientFilter $clientFilter): array
    {
        return [];
    }
}
