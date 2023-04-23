<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class PreSaveClientEvent extends Event
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
    }
}
