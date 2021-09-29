<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class PreSaveClientEvent extends Event
{
    /**
     * @var AbstractClient
     */
    private $client;

    public function __construct(AbstractClient $client)
    {
        $this->client = $client;
    }

    public function getClient(): AbstractClient
    {
        return $this->client;
    }

    public function setClient(AbstractClient $client): void
    {
        $this->client = $client;
    }
}
