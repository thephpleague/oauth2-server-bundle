<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use DateTimeImmutable;
use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverter;
use League\Bundle\OAuth2ServerBundle\League\Repository\AuthCodeRepository;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;

final class AuthCodeRepositoryTest extends AbstractIntegrationTest
{
    public function testAuthCodeRevoking(): void
    {
        $identifier = 'foo';

        $authCode = new AuthorizationCode(
            $identifier,
            new DateTimeImmutable(),
            new Client('bar', 'baz'),
            null,
            []
        );

        $this->authCodeManager->save($authCode);

        $this->assertSame($authCode, $this->authCodeManager->find($identifier));

        $authCodeRepository = new AuthCodeRepository($this->authCodeManager, $this->clientManager, new ScopeConverter());

        $authCodeRepository->revokeAuthCode($identifier);

        $this->assertTrue($authCode->isRevoked());
        $this->assertSame($authCode, $this->authCodeManager->find($identifier));
    }
}
