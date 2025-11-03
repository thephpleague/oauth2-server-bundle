<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
        string $clientFqcn,
    ) {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
        $this->clientFqcn = $clientFqcn;
    }

    public function find(string $identifier): ?ClientInterface
    {
        $repository = $this->entityManager->getRepository($this->clientFqcn);

        return $repository->findOneBy(['identifier' => $identifier]);
    }

    public function save(ClientInterface $client): void
    {
        $event = $this->dispatcher->dispatch(new PreSaveClientEvent($client), OAuth2Events::PRE_SAVE_CLIENT);
        $client = $event->getClient();

        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function remove(ClientInterface $client): void
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
        $qb = $repository->createQueryBuilder('c');
        self::setQueryBuilderFilters($qb, $clientFilter);

        /** @var list<AbstractClient> */
        return $qb->getQuery()->getResult();
    }

    private static function setQueryBuilderFilters(QueryBuilder $qb, ?ClientFilter $clientFilter): void
    {
        if (null === $clientFilter || false === $clientFilter->hasFilters()) {
            return;
        }

        self::setFieldFilter($qb, 'grants', $clientFilter->getGrants());

        self::setFieldFilter($qb, 'redirect_uris', $clientFilter->getRedirectUris());

        self::setFieldFilter($qb, 'scopes', $clientFilter->getScopes());
    }

    /**
     * @param list<Scope>|list<RedirectUri>|list<Grant> $values
     */
    private static function setFieldFilter(QueryBuilder $qb, string $field, array $values): void
    {
        foreach ($values as $key => $value) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('c.' . $field, ':' . $field . $key),
                    $qb->expr()->like('c.' . $field, ':space_' . $field . $key),
                    $qb->expr()->like('c.' . $field, ':' . $field . '_space' . $key),
                    $qb->expr()->like('c.' . $field, ':space_' . $field . '_space' . $key),
                )
            )
                ->setParameter($field . $key, (string) $value)
                ->setParameter('space_' . $field . $key, '% ' . (string) $value)
                ->setParameter($field . '_space' . $key, (string) $value . ' %')
                ->setParameter('space_' . $field . '_space' . $key, '% ' . (string) $value . ' %')
            ;
        }
    }
}
