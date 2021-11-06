<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

final class OAuth2TokenTest extends TestCase
{
    public function testTokenSerialization(): void
    {
        $user = new User();
        $accessTokenId = 'accessTokenId';
        $oauthClientId = 'oauthClientId';
        $scopes = [FixtureFactory::FIXTURE_SCOPE_FIRST];
        $rolePrefix = 'ROLE_OAUTH2_';

        $token = new OAuth2Token($user, $accessTokenId, $oauthClientId, $scopes, $rolePrefix);

        /** @var OAuth2Token $unserializedToken */
        $unserializedToken = unserialize(serialize($token));

        $this->assertSame($user->getUsername(), $unserializedToken->getUser()->getUsername());
        $this->assertSame($accessTokenId, $token->getCredentials());
        $this->assertSame($oauthClientId, $token->getOAuthClientId());
        $this->assertSame($scopes, $token->getScopes());
        $this->assertSame([sprintf('%s%s', $rolePrefix, strtoupper($scopes[0]))], $token->getRoleNames());

        $this->assertFalse($unserializedToken->isAuthenticated());
    }
}
