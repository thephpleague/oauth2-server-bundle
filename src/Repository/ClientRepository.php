<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Repository;

use League\Bundle\OAuth2ServerBundle\Entity\Client as ClientEntity;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\MigratingPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ClientRepository implements ClientRepositoryInterface
{
    private readonly PasswordHasherInterface $passwordHasher;

    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        ?PasswordHasherInterface $passwordHasher = null,
    ) {
        if (null === $passwordHasher) {
            trigger_deprecation('league/oauth2-server-bundle', '1.2', 'Not passing a "%s" to "%s" is deprecated since version 1.2 and will be required in 2.0.', PasswordHasherInterface::class, self::class);

            // Default to a migrating hasher so legacy plaintext secrets keep validating
            // (and get upgraded on first use) while never bypassing the hasher API.
            $passwordHasher = new MigratingPasswordHasher(new NativePasswordHasher(), new PlaintextPasswordHasher());
        }

        $this->passwordHasher = $passwordHasher;
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->clientManager->find($clientIdentifier);

        if (null === $client) {
            return null;
        }

        return $this->buildClientEntity($client);
    }

    public function validateClient(string $clientIdentifier, #[\SensitiveParameter] ?string $clientSecret, ?string $grantType): bool
    {
        $client = $this->clientManager->find($clientIdentifier);

        if (null === $client) {
            return false;
        }

        if (!$client->isActive()) {
            return false;
        }

        if (!$this->isGrantSupported($client, $grantType)) {
            return false;
        }

        if (!$client->isConfidential()) {
            return true;
        }

        $storedSecret = (string) $client->getSecret();
        $inputSecret = (string) $clientSecret;

        $secretIsValid = $this->passwordHasher->verify($storedSecret, $inputSecret);

        if ($secretIsValid && $this->passwordHasher->needsRehash($storedSecret)) {
            $client->setSecret($this->passwordHasher->hash($inputSecret));
            $this->clientManager->save($client);
        }

        return $secretIsValid;
    }

    private function buildClientEntity(ClientInterface $client): ClientEntity
    {
        $clientEntity = new ClientEntity();
        $clientEntity->setName($client->getName());
        $clientEntity->setIdentifier($client->getIdentifier());
        $clientEntity->setRedirectUri(array_map(strval(...), $client->getRedirectUris()));
        $clientEntity->setConfidential($client->isConfidential());
        $clientEntity->setAllowPlainTextPkce($client->isPlainTextPkceAllowed());
        $grantTypes = array_map(strval(...), $client->getGrants());
        $clientEntity->setAllowedGrantTypes($grantTypes);

        return $clientEntity;
    }

    private function isGrantSupported(ClientInterface $client, ?string $grant): bool
    {
        if (null === $grant) {
            return true;
        }

        $grants = $client->getGrants();

        if (empty($grants)) {
            return true;
        }

        return \in_array($grant, $client->getGrants());
    }
}
