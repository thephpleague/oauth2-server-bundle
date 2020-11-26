<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\League\AuthorizationServer;

use League\OAuth2\Server\AuthorizationServer;

final class GrantConfigurator
{
    /**
     * @var iterable|GrantTypeInterface[]
     */
    private $grants;

    public function __construct(iterable $grants)
    {
        $this->grants = $grants;
    }

    public function __invoke(AuthorizationServer $authorizationServer): void
    {
        foreach ($this->grants as $grant) {
            $authorizationServer->enableGrantType($grant, $grant->getAccessTokenTTL());
        }
    }
}
