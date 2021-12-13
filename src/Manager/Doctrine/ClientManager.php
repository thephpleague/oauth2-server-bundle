<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManager implements ClientManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var class-string<AbstractClient>
     */
    private $clientFqcn;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param class-string<AbstractClient> $clientFqcn
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        string $clientFqcn
    ) {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
        $this->clientFqcn = $clientFqcn;
    }

    public function find(string $identifier): ?AbstractClient
    {
        $repository = $this->entityManager->getRepository($this->clientFqcn);

        return $repository->findOneBy(['identifier' => $identifier]);
    }

    public function save(AbstractClient $client): void
    {
        /** @var PreSaveClientEvent $event */
        $event = $this->dispatcher->dispatch(new PreSaveClientEvent($client), OAuth2Events::PRE_SAVE_CLIENT);
        $client = $event->getClient();

        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function remove(AbstractClient $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    /**
     * @return list<AbstractClient>
     */
    public function list(?ClientFilter $clientFilter): array
    {
        $repository = $this->entityManager->getRepository($this->clientFqcn);
        $criteria = self::filterToCriteria($clientFilter);

        /** @var list<AbstractClient> */
        return $repository->findBy($criteria);
    }

    /**
     * @return array{grants?: list<Grant>, redirect_uris?: list<RedirectUri>, scopes?: list<Scope>}
     */
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
