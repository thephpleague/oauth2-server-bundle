<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManager implements ClientManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

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
        ObjectManager $objectManager,
        EventDispatcherInterface $dispatcher,
        string $clientFqcn
    )
    {
        $this->objectManager = $objectManager;
        $this->dispatcher = $dispatcher;
        $this->clientFqcn = $clientFqcn;
    }

    public function find(string $identifier): ?ClientInterface
    {
        $repository = $this->objectManager->getRepository($this->clientFqcn);

        return $repository->findOneBy(['identifier' => $identifier]);
    }

    public function save(ClientInterface $client): void
    {
        $event = $this->dispatcher->dispatch(new PreSaveClientEvent($client), OAuth2Events::PRE_SAVE_CLIENT);
        $client = $event->getClient();

        $this->objectManager->persist($client);
        $this->objectManager->flush();
    }

    public function remove(ClientInterface $client): void
    {
        $this->objectManager->remove($client);
        $this->objectManager->flush();
    }

    /**
     * @return list<AbstractClient>
     */
    public function list(?ClientFilter $clientFilter): array
    {
        $repository = $this->objectManager->getRepository($this->clientFqcn);
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
