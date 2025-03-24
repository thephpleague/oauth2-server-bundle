<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\AuthorizationServer;

use League\OAuth2\Server\Grant\GrantTypeInterface as LeagueGrantTypeInterface;

/**
 * @deprecated use League\OAuth2\Server\Grant\GrantTypeInterface with accessTokenTTL tag attribute instead
 */
interface GrantTypeInterface extends LeagueGrantTypeInterface
{
    public function getAccessTokenTTL(): ?\DateInterval;
}
