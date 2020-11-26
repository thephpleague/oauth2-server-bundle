<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

final class ClientManager implements ClientManagerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?Client
    {
        return $this->entityManager->find(Client::class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function list(?ClientFilter $clientFilter): array
    {
        $repository = $this->entityManager->getRepository(Client::class);
        $criteria = self::filterToCriteria($clientFilter);

        return $repository->findBy($criteria);
    }

    private static function filterToCriteria(?ClientFilter $clientFilter): array
    {
        if (null === $clientFilter || false === $clientFilter->hasFilters()) {
            return [];
        }

        $criteria = [];

        $grants = $clientFilter->getGrants();
        if ($grants) {
            $criteria['grants'] = $grants;
        }

        $redirectUris = $clientFilter->getRedirectUris();
        if ($redirectUris) {
            $criteria['redirect_uris'] = $redirectUris;
        }

        $scopes = $clientFilter->getScopes();
        if ($scopes) {
            $criteria['scopes'] = $scopes;
        }

        return $criteria;
    }
}
