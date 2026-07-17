<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class JwtLeewayConfigurationTest extends AbstractAcceptanceTest
{
    protected function setUp(): void
    {
        $this->client = self::createClient(['environment' => 'jwt_leeway']);
        $this->application = new Application($this->client->getKernel());
    }

    public function testLeewayConfigurationIsSet(): void
    {
        /** @var AccessTokenRepository $tokenRepository */
        $tokenRepository = $this->client->getContainer()->get(AccessTokenRepository::class);
        $validator = $this->client->getContainer()->get(BearerTokenValidator::class);

        $expected = new BearerTokenValidator($tokenRepository, new \DateInterval('PT60S'));
        $this->assertEquals($expected, $validator);
    }
}
