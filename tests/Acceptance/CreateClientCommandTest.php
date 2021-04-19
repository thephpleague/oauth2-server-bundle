<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateClientCommandTest extends AbstractAcceptanceTest
{
    public function testCreateClient(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
    }

    public function testCreateClientWithIdentifier(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
        $this->assertStringContainsString('foobar', $output);

        /** @var Client $client */
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('My Awesome OAuth Client', $client->getName());
        $this->assertTrue($client->isConfidential());
        $this->assertNotEmpty($client->getSecret());
        $this->assertFalse($client->isPlainTextPkceAllowed());
    }

    public function testCreatePublicClientWithIdentifier(): void
    {
        $clientIdentifier = 'foobar test';
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => $clientIdentifier,
            '--public' => true,
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
        $this->assertStringContainsString($clientIdentifier, $output);

        /** @var Client $client */
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find($clientIdentifier);
        $this->assertInstanceOf(Client::class, $client);
        $this->assertFalse($client->isConfidential());
        $this->assertNull($client->getSecret());
        $this->assertFalse($client->isPlainTextPkceAllowed());
    }

    public function testCannotCreatePublicClientWithSecret(): void
    {
        $clientIdentifier = 'foobar test';
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => $clientIdentifier,
            'secret' => 'foo',
            '--public' => true,
        ]);

        $this->assertSame(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The client cannot have a secret and be public.', $output);
        $this->assertStringNotContainsString($clientIdentifier, $output);

        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find($clientIdentifier);
        $this->assertNull($client);
    }

    public function testCreateClientWithSecret(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar',
            'secret' => 'quzbaz',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);

        /** @var Client $client */
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('quzbaz', $client->getSecret());
        $this->assertTrue($client->isConfidential());
        $this->assertFalse($client->isPlainTextPkceAllowed());
    }

    public function testCreateClientWhoIsAllowedToUsePlainPkceChallengeMethod(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar-123',
            '--allow-plain-text-pkce' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);

        /** @var Client $client */
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar-123');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertTrue($client->isPlainTextPkceAllowed());
    }

    public function testCreateClientWithRedirectUris(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar',
            '--redirect-uri' => ['http://example.org', 'http://example.org'],
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertCount(2, $client->getRedirectUris());
    }

    public function testCreateClientWithGrantTypes(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar',
            '--grant-type' => ['password', 'client_credentials'],
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertCount(2, $client->getGrants());
    }

    public function testCreateClientWithScopes(): void
    {
        $command = $this->application->find('league:oauth2-server:create-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'name' => 'My Awesome OAuth Client',
            'identifier' => 'foobar',
            '--scope' => ['foo', 'bar'],
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('New oAuth2 client created successfully', $output);
        $client = $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
            ->find('foobar');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertCount(2, $client->getScopes());
    }
}
