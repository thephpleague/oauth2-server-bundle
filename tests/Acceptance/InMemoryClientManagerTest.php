<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ClientManager as InMemoryClientManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Tests\Acceptance\AbstractAcceptanceTest;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @TODO   This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 */
final class InMemoryClientManagerTest extends AbstractAcceptanceTest
{
    public function testSaveClientWithoutScopeAddDefaultScopes(): void
    {
        $inMemoryClientManager = new InMemoryClientManager(self::getContainer()->get(EventDispatcherInterface::class));

        $inMemoryClientManager->save($client = new Client('client', 'client', 'secret'));

        $this->assertEquals(
            [new Scope(FixtureFactory::FIXTURE_SCOPE_SECOND)],
            $inMemoryClientManager->find($client->getIdentifier())->getScopes()
        );
    }
}
