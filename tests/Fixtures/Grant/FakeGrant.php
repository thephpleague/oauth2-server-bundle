<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Grant;

use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

final class FakeGrant extends AbstractGrant implements GrantTypeInterface
{
    public function getIdentifier(): string
    {
        return 'fake_grant';
    }

    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, \DateInterval $accessTokenTTL): ResponseTypeInterface
    {
        return new Response();
    }
}
