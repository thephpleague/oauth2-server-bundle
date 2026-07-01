<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class RehashClientSecretsCommandTest extends AbstractAcceptanceTest
{
    public function testHashPlainTextSecrets(): void
    {
        $connection = $this->getConnection();

        $this->insertClientWithSecret($connection, 'client-plain', 'plain-text-secret');

        $commandTester = $this->buildCommandTester();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('1 secret(s) rehashed', $commandTester->getDisplay());

        $hashedSecret = $connection->fetchOne('SELECT secret FROM oauth2_client WHERE identifier = ?', ['client-plain']);
        $this->assertStringStartsWith('$2y$', $hashedSecret);
        $this->assertTrue($this->getHasher()->verify($hashedSecret, 'plain-text-secret'));
    }

    public function testSkipAlreadyHashedSecrets(): void
    {
        $connection = $this->getConnection();

        // Pre-hash with the same hasher the command uses so needsRehash() returns false
        $hashedByHasher = $this->getHasher()->hash('already-hashed');
        $this->insertClientWithSecret($connection, 'client-hashed', $hashedByHasher);

        $commandTester = $this->buildCommandTester();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('0 secret(s) rehashed', $commandTester->getDisplay());
        $this->assertStringContainsString('1 already hashed', $commandTester->getDisplay());

        $storedSecret = $connection->fetchOne('SELECT secret FROM oauth2_client WHERE identifier = ?', ['client-hashed']);
        $this->assertSame($hashedByHasher, $storedSecret);
    }

    public function testSkipPublicClients(): void
    {
        $connection = $this->getConnection();

        $connection->insert('oauth2_client', [
            'identifier' => 'client-public',
            'name' => 'Public Client',
            'secret' => null,
            'active' => 1,
            'allowPlainTextPkce' => 0,
        ]);

        $commandTester = $this->buildCommandTester();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('0 secret(s) rehashed', $commandTester->getDisplay());
    }

    public function testMixedClients(): void
    {
        $connection = $this->getConnection();
        $hasher = $this->getHasher();

        $this->insertClientWithSecret($connection, 'plain-1', 'secret-one');
        $this->insertClientWithSecret($connection, 'plain-2', 'secret-two');

        // Pre-hash with the same hasher so needsRehash() returns false
        $this->insertClientWithSecret($connection, 'already-hashed', $hasher->hash('hashed-secret'));

        $connection->insert('oauth2_client', [
            'identifier' => 'public-client',
            'name' => 'Public',
            'secret' => null,
            'active' => 1,
            'allowPlainTextPkce' => 0,
        ]);

        $commandTester = $this->buildCommandTester();

        $this->assertStringContainsString('2 secret(s) rehashed', $commandTester->getDisplay());
        $this->assertStringContainsString('1 already hashed', $commandTester->getDisplay());

        $this->assertTrue($hasher->verify(
            $connection->fetchOne('SELECT secret FROM oauth2_client WHERE identifier = ?', ['plain-1']),
            'secret-one'
        ));
        $this->assertTrue($hasher->verify(
            $connection->fetchOne('SELECT secret FROM oauth2_client WHERE identifier = ?', ['plain-2']),
            'secret-two'
        ));
    }

    private function getConnection(): Connection
    {
        return $this->client->getContainer()->get('database_connection');
    }

    private function getHasher(): PasswordHasherInterface
    {
        return $this->client->getContainer()->get('league.oauth2_server.password_hasher');
    }

    private function insertClientWithSecret(Connection $connection, string $identifier, string $secret): void
    {
        $connection->insert('oauth2_client', [
            'identifier' => $identifier,
            'name' => 'Test Client',
            'secret' => $secret,
            'active' => 1,
            'allowPlainTextPkce' => 0,
        ]);
    }

    private function buildCommandTester(): CommandTester
    {
        $command = $this->application->find('league:oauth2-server:rehash-client-secrets');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        return $commandTester;
    }
}
