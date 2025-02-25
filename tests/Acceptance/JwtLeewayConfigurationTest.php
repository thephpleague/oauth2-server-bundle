<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository;
use League\Bundle\OAuth2ServerBundle\Tests\TestKernel;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class JwtLeewayConfigurationTest extends AbstractAcceptanceTest
{
    protected function setUp(): void
    {
        $this->client = self::createClient();
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

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel(
            'test',
            false,
            [
                'public_key' => '%env(PUBLIC_KEY_PATH)%',
                'jwt_leeway' => 'PT60S',
            ]
        );
    }
}
