<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearExpiredTokensCommandTest extends AbstractAcceptanceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        FixtureFactory::initializeFixtures(
            $this->client->getContainer()->get(ScopeManagerInterface::class),
            $this->client->getContainer()->get(ClientManagerInterface::class),
            $this->client->getContainer()->get(AccessTokenManagerInterface::class),
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class),
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class),
            $this->client->getContainer()->get(DeviceCodeManagerInterface::class)
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testClearExpiredAccessAndRefreshTokensAndAuthCodes(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleared 1 expired access token.', $output);
        $this->assertStringContainsString('Cleared 1 expired refresh token.', $output);
        $this->assertStringContainsString('Cleared 1 expired auth code.', $output);
        $this->assertStringContainsString('Cleared 1 expired device code.', $output);

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $this->assertNull(
            $this->client->getContainer()->get(AccessTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_REFRESH_TOKEN_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_AUTH_CODE_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(DeviceCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_DEVICE_CODE_EXPIRED
            )
        );
    }

    public function testClearExpiredAccessTokens(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'command' => $command->getName(),
            '--access-tokens' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleared 1 expired access token.', $output);
        $this->assertStringNotContainsString('Cleared 1 expired refresh token.', $output);
        $this->assertStringNotContainsString('Cleared 1 expired auth code.', $output);

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $this->assertNull(
            $this->client->getContainer()->get(AccessTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED
            )
        );
        $this->assertInstanceOf(
            RefreshToken::class,
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_REFRESH_TOKEN_EXPIRED
            )
        );
        $this->assertInstanceOf(
            AuthorizationCode::class,
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_AUTH_CODE_EXPIRED
            )
        );
    }

    public function testClearExpiredRefreshTokens(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'command' => $command->getName(),
            '--refresh-tokens' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('Cleared 1 expired access token.', $output);
        $this->assertStringContainsString('Cleared 1 expired refresh token.', $output);
        $this->assertStringNotContainsString('Cleared 1 expired auth code.', $output);

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $this->assertInstanceOf(
            AccessToken::class,
            $this->client->getContainer()->get(AccessTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_REFRESH_TOKEN_EXPIRED
            )
        );
        $this->assertInstanceOf(
            AuthorizationCode::class,
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_AUTH_CODE_EXPIRED
            )
        );
    }

    public function testClearExpiredAuthCodes(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'command' => $command->getName(),
            '--auth-codes' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('Cleared 1 expired access token.', $output);
        $this->assertStringNotContainsString('Cleared 1 expired refresh token.', $output);
        $this->assertStringContainsString('Cleared 1 expired auth code.', $output);

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $this->assertInstanceOf(
            AccessToken::class,
            $this->client->getContainer()->get(AccessTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED
            )
        );
        $this->assertInstanceOf(
            RefreshToken::class,
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_REFRESH_TOKEN_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(AuthorizationCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_AUTH_CODE_EXPIRED
            )
        );
    }

    public function testClearExpiredDeviceCodes(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'command' => $command->getName(),
            '--device-codes' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('Cleared 1 expired access token.', $output);
        $this->assertStringNotContainsString('Cleared 1 expired refresh token.', $output);
        $this->assertStringContainsString('Cleared 1 expired device code.', $output);

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $this->assertInstanceOf(
            AccessToken::class,
            $this->client->getContainer()->get(AccessTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_ACCESS_TOKEN_EXPIRED
            )
        );
        $this->assertInstanceOf(
            RefreshToken::class,
            $this->client->getContainer()->get(RefreshTokenManagerInterface::class)->find(
                FixtureFactory::FIXTURE_REFRESH_TOKEN_EXPIRED
            )
        );
        $this->assertNull(
            $this->client->getContainer()->get(DeviceCodeManagerInterface::class)->find(
                FixtureFactory::FIXTURE_DEVICE_CODE_EXPIRED
            )
        );
    }

    private function command(): Command
    {
        return $this->application->find('league:oauth2-server:clear-expired-tokens');
    }
}
