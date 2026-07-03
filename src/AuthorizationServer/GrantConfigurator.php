<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\AuthorizationServer;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\GrantTypeInterface as LeagueGrantTypeInterface;

/**
 * @deprecated
 */
final class GrantConfigurator
{
    /**
     * @var iterable<GrantTypeInterface>
     */
    private iterable $grants;

    /**
     * @param iterable<GrantTypeInterface> $grants
     */
    public function __construct(iterable $grants)
    {
        $this->grants = $grants;
    }

    public function __invoke(AuthorizationServer $authorizationServer): void
    {
        foreach ($this->grants as $grant) {
            if ($grant instanceof GrantTypeInterface) {
                trigger_deprecation('league/oauth2-server-bundle', '1.2', '%s implements custom grant using %s interface which is deprecated. Use %s interface and tag %s with accessTokenTTL attribute instead. See : %s',
                    $grant::class,
                    GrantTypeInterface::class,
                    LeagueGrantTypeInterface::class,
                    'league.oauth2_server.authorization_server.grant',
                    'https://github.com/thephpleague/oauth2-server-bundle/blob/v1.2.0/docs/implementing-custom-grant-type.md'
                );

                $authorizationServer->enableGrantType($grant, $grant->getAccessTokenTTL());
            }
        }
    }
}
