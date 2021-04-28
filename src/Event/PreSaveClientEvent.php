<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class PreSaveClientEvent extends Event
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
