<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;
use Symfony\Component\Console\Tester\CommandTester;

final class UpdateClientCommandTest extends AbstractAcceptanceTest
{
    /**
     * @dataProvider updateRelatedModelsDataProvider
     */
    public function testUpdateRelatedModels(string $argument, array $initial, array $toAdd, array $toRemove, array $expected, string $getter, string $setter): void
    {
        $client = $this->fakeAClient('foobar');
        $client->{$setter}(...$initial);

        $this->getClientManager()->save($client);
        $this->assertCount(\count($initial), $client->{$getter}());

        $command = $this->application->find('league:oauth2-server:update-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
            sprintf('--add-%s', $argument) => $toAdd,
            sprintf('--remove-%s', $argument) => $toRemove,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('OAuth2 client updated successfully.', $output);
        $this->assertEquals($expected, $client->{$getter}());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Cannot specify "%s" in either "--add-%2$s" and "--remove-%2$s".', $toAdd[0], $argument));

        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
            sprintf('--add-%s', $argument) => [$toAdd[0]],
            sprintf('--remove-%s', $argument) => [$toAdd[0]],
        ]);
    }

    public function updateRelatedModelsDataProvider(): iterable
    {
        yield 'redirect uris' => [
            'redirect-uri',
            [new RedirectUri('http://one.com'), new RedirectUri('http://two.com')],
            ['http://three.com', 'http://four.com'],
            ['http://one.com', 'http://five.com'],
            [new RedirectUri('http://two.com'), new RedirectUri('http://three.com'), new RedirectUri('http://four.com')],
            'getRedirectUris',
            'setRedirectUris',
        ];

        yield 'grant types' => [
            'grant-type',
            [new Grant('one'), new Grant('two')],
            ['three', 'four'],
            ['one', 'five'],
            [new Grant('two'), new Grant('three'), new Grant('four')],
            'getGrants',
            'setGrants',
        ];

        yield 'scopes' => [
            'scope',
            [new Scope('one'), new Scope('two')],
            ['three', 'four'],
            ['one', 'five'],
            [new Scope('two'), new Scope('three'), new Scope('four')],
            'getScopes',
            'setScopes',
        ];
    }

    public function testUpdateActive(): void
    {
        $client = $this->fakeAClient('foobar');
        $this->getClientManager()->save($client);
        $this->assertTrue($client->isActive());

        $command = $this->application->find('league:oauth2-server:update-client');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
            '--deactivate' => null,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('OAuth2 client updated successfully.', $output);
        $updatedClient = $this->getClientManager()->find($client->getIdentifier());
        $this->assertFalse($updatedClient->isActive());

        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
            '--activate' => null,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('OAuth2 client updated successfully.', $output);
        $updatedClient = $this->getClientManager()->find($client->getIdentifier());
        $this->assertTrue($updatedClient->isActive());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot specify "--activate" and "--deactivate" at the same time.');

        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
            '--activate' => null,
            '--deactivate' => null,
        ]);
    }

    private function fakeAClient($identifier): Client
    {
        return new Client('name', $identifier, 'quzbaz');
    }

    private function getClientManager(): ClientManagerInterface
    {
        return $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class);
    }
}
