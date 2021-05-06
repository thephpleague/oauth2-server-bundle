<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

/**
 * Sets default scopes to the client before being saved by a ClientManager if no scope is specified.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class AddClientDefaultScopesListener
{
    /**
     * @var list<string>
     */
    private $defaultScopes;

    /**
     * @param list<string> $defaultScopes
     */
    public function __construct(array $defaultScopes)
    {
        $this->defaultScopes = $defaultScopes;
    }

    public function __invoke(PreSaveClientEvent $event): void
    {
        $client = $event->getClient();
        if ([] !== $client->getScopes()) {
            return;
        }

        $client->setScopes(...array_map(static function (string $scope): Scope {
            return new Scope($scope);
        }, $this->defaultScopes));
    }
}
