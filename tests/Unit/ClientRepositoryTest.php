<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ClientManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PasswordHasher\Hasher\MigratingPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ClientRepositoryTest extends TestCase
{
    private ClientRepository $repository;
    private ClientManager $clientManager;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        $this->hasher = new NativePasswordHasher();
        $this->clientManager = new ClientManager(new EventDispatcher());
        $this->repository = new ClientRepository($this->clientManager, $this->hasher);
    }

    /**
     * @group legacy
     */
    public function testValidateClientSucceedsWithoutPasswordHasher(): void
    {
        $plainSecret = 'my-plain-secret';
        $client = new Client('My App', 'my-client', $plainSecret);
        $this->clientManager->save($client);

        $repositoryWithoutHasher = new ClientRepository($this->clientManager);

        $this->assertTrue(
            $repositoryWithoutHasher->validateClient('my-client', $plainSecret, null)
        );
    }

    /**
     * Regression test: a client created via CreateClientCommand stores its secret
     * pre-hashed with NativePasswordHasher::hash() (which uses SHA-512+bcrypt for
     * long passwords). validateClient() must verify the plain secret correctly.
     */
    public function testValidateClientSucceedsWithSecretHashedByHasher(): void
    {
        $plainSecret = 'my-plain-secret';
        $client = new Client('My App', 'my-client', $this->hasher->hash($plainSecret));
        $this->clientManager->save($client);

        $this->assertTrue(
            $this->repository->validateClient('my-client', $plainSecret, null)
        );
    }

    /**
     * Regression test for the original bug: a long secret (>72 bytes, as produced by
     * CreateClientCommand's sha512 fallback) must survive a round-trip through
     * hash() and verify() without silent truncation.
     */
    public function testValidateClientSucceedsWithLongSecretOver72Bytes(): void
    {
        // Simulate the auto-generated secret from CreateClientCommand (sha512 = 128 hex chars).
        $longSecret = hash('sha512', random_bytes(32));
        $this->assertGreaterThan(72, \strlen($longSecret));

        $client = new Client('My App', 'my-client-long', $this->hasher->hash($longSecret));
        $this->clientManager->save($client);

        $this->assertTrue(
            $this->repository->validateClient('my-client-long', $longSecret, null)
        );
    }

    public function testValidateClientFailsWithWrongSecret(): void
    {
        $client = new Client('My App', 'my-client', $this->hasher->hash('my-plain-secret'));
        $this->clientManager->save($client);

        $this->assertFalse(
            $this->repository->validateClient('my-client', 'wrong-secret', null)
        );
    }

    /**
     * Plain-text migration path: clients whose secrets were stored as plain text
     * (before hashing was introduced) are verified and then automatically
     * rehashed on first successful validation.
     */
    public function testValidateClientSucceedsWithPlainTextLegacySecret(): void
    {
        $client = new Client('Legacy App', 'legacy-client', 'plain-text-secret');
        $this->clientManager->save($client);

        $migratingHasher = new MigratingPasswordHasher($this->hasher, new PlaintextPasswordHasher());
        $repositoryWithMigratingHasher = new ClientRepository($this->clientManager, $migratingHasher);

        $this->assertTrue(
            $repositoryWithMigratingHasher->validateClient('legacy-client', 'plain-text-secret', null)
        );

        // After successful plain-text validation the secret must have been upgraded to a hash.
        $upgraded = $this->clientManager->find('legacy-client');
        $this->assertNotSame('plain-text-secret', $upgraded->getSecret());
        $this->assertTrue($this->hasher->verify($upgraded->getSecret(), 'plain-text-secret'));
    }

    /**
     * Simulates what Doctrine does when hydrating a client from the database:
     * the $secret field is set directly to the stored hash value via reflection,
     * bypassing the constructor. setSecret() replicates this path.
     */
    public function testValidateClientSucceedsWhenSecretSetDirectlyAsHash(): void
    {
        $plainSecret = 'my-plain-secret';

        $client = new Client('My App', 'my-client-db', null);
        $client->setSecret($this->hasher->hash($plainSecret));
        $this->clientManager->save($client);

        $this->assertTrue(
            $this->repository->validateClient('my-client-db', $plainSecret, null)
        );
    }

    public function testValidateClientReturnsFalseForUnknownClient(): void
    {
        $this->assertFalse(
            $this->repository->validateClient('nonexistent', 'any-secret', null)
        );
    }

    public function testValidateClientReturnsFalseForInactiveClient(): void
    {
        $client = (new Client('My App', 'inactive-client', $this->hasher->hash('secret')))->setActive(false);
        $this->clientManager->save($client);

        $this->assertFalse(
            $this->repository->validateClient('inactive-client', 'secret', null)
        );
    }
}
