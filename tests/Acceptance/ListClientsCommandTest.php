<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ListClientsCommandTest extends AbstractAcceptanceTest
{
    public function testListClients(): void
    {
        $client = $this->fakeAClient('client', 'foobar');
        $this->getClientManager()->save($client);

        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
        $output = $commandTester->getDisplay();
        $expected = file_get_contents(__DIR__ . '/resource/list-clients.txt');
        $this->assertEquals(trim($expected), trim($output));
    }

    public function testListClientsWithClientHavingNoSecret(): void
    {
        $client = $this->fakeAClient('client', 'foobar', null);
        $this->getClientManager()->save($client);

        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
        $output = $commandTester->getDisplay();
        $expected = file_get_contents(__DIR__ . '/resource/list-clients-with-client-having-no-secret.txt');

        $this->assertEquals(trim($expected), trim($output));
    }

    public function testListClientsEmpty(): void
    {
        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
        $output = $commandTester->getDisplay();
        $expected = file_get_contents(__DIR__ . '/resource/list-client-empty.txt');

        $this->assertEquals(trim($expected), trim($output));
    }

    public function testListClientColumns(): void
    {
        $scopes = [
            new Scope('client-scope-1'),
            new Scope('client-scope-2'),
        ];

        $redirectUris = [
            new RedirectUri('http://client-redirect-url'),
        ];

        $client =
            $this
                ->fakeAClient('client', 'foobar')
                ->setScopes(...$scopes)
                ->setRedirectUris(...$redirectUris)
        ;
        $this->getClientManager()->save($client);

        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--columns' => ['identifier', 'scope'],
        ]);
        $output = $commandTester->getDisplay();

        $expected = file_get_contents(__DIR__ . '/resource/list-client-columns.txt');

        $this->assertEquals(trim($expected), trim($output));
    }

    public function testListFiltersClients(): void
    {
        $clientA = $this->fakeAClient('client', 'client-a', 'client-a-secret');
        $this->getClientManager()->save($clientA);

        $clientB = $this
            ->fakeAClient('client', 'client-b', 'client-b-secret')
            ->setScopes(new Scope('client-b-scope'))
        ;
        $this->getClientManager()->save($clientB);

        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--scope' => $clientB->getScopes(),
        ]);
        $output = $commandTester->getDisplay();

        $expected = file_get_contents(__DIR__ . '/resource/list-filters-clients.txt');

        $this->assertEquals(trim($expected), trim($output));
    }

    private function fakeAClient(string $name, string $identifier, ?string $secret = 'quzbaz'): Client
    {
        return new Client($name, $identifier, $secret);
    }

    private function getClientManager(): ClientManagerInterface
    {
        return $this->client
            ->getContainer()
            ->get(ClientManagerInterface::class)
        ;
    }

    private function command(): Command
    {
        return $this->application->find('league:oauth2-server:list-clients');
    }
}
