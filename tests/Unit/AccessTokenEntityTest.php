<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Entity\AccessToken;
use League\Bundle\OAuth2ServerBundle\Tests\TestHelper;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use PHPUnit\Framework\TestCase;

final class AccessTokenEntityTest extends TestCase
{
    public function testExtraClaimsToJwtClaims(): void
    {
        $accessToken = new AccessToken(['foo' => 'bar', 'baz' => ['qux', 'quux']]);
        $accessToken->setClient($this->createStub(ClientEntityInterface::class));
        $accessToken->setIdentifier('test-access-token');
        $accessToken->setExpiryDateTime(new \DateTimeImmutable('+1 hour'));
        $accessToken->setPrivateKey(new CryptKey(file_get_contents(__DIR__ . '/../Fixtures/private.key')));

        $token = TestHelper::parseJwtToken($accessToken->toString());

        $this->assertSame($token['payload']['foo'], 'bar');
        $this->assertSame($token['payload']['baz'], ['qux', 'quux']);
    }

    /**
     * @dataProvider provideReservedClaims
     */
    public function testReservedClaimsCannotBeUsedAsExtraClaims(string $claim): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $accessToken = new AccessToken([$claim => 'foobar']);
        $accessToken->setClient($this->createStub(ClientEntityInterface::class));
        $accessToken->setIdentifier('test-access-token');
        $accessToken->setExpiryDateTime(new \DateTimeImmutable('+1 hour'));
        $accessToken->setPrivateKey(new CryptKey(file_get_contents(__DIR__ . '/../Fixtures/private.key')));

        $accessToken->toString();
    }

    /**
     * @return \Generator<array{string}>
     */
    public static function provideReservedClaims(): \Generator
    {
        yield ['iss'];
        yield ['aud'];
        yield ['sub'];
        yield ['jti'];
        yield ['iat'];
        yield ['nbf'];
        yield ['exp'];
        yield ['scopes'];
    }
}
