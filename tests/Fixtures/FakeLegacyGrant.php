<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

// TODO remove code bloc when bundle interface and configurator will be deleted
/**
 * @deprecated
 */
final class FakeLegacyGrant extends AbstractGrant implements GrantTypeInterface
{
    public function getIdentifier(): string
    {
        return 'fake_legacy_grant';
    }

    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, \DateInterval $accessTokenTTL): ResponseTypeInterface
    {
        return new Response();
    }

    public function getAccessTokenTTL(): ?\DateInterval
    {
        return new \DateInterval('PT5H');
    }
}
