<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker\DoctrineCredentialsRevoker;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;

/**
 * @TODO   This should be in the Integration tests folder but the current tests infrastructure would need improvements first.
 * @covers \League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker\DoctrineCredentialsRevoker
 */
final class DoctrineCredentialsRevokerTest extends AbstractAcceptanceTest
{
    public function testRevokesAllCredentialsForUser(): void
    {
        $userIdentifier = FixtureFactory::FIXTURE_USER;

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $em->persist($client = new Client('client', 'secret'));

        $authCode = $this->buildAuthCode('foo', '+1 minute', $client, $userIdentifier);
        $accessToken = $this->buildAccessToken('bar', '+1 minute', $client, $userIdentifier);
        $refreshToken = $this->buildRefreshToken('baz', '+1 minute', $accessToken);

        $em->persist($authCode);
        $em->persist($accessToken);
        $em->persist($refreshToken);
        $em->flush();

        $revoker = new DoctrineCredentialsRevoker($em);

        $revoker->revokeCredentialsForUser(FixtureFactory::createUser());

        $em->refresh($authCode);
        $em->refresh($accessToken);
        $em->refresh($refreshToken);

        $this->assertTrue($authCode->isRevoked());
        $this->assertTrue($accessToken->isRevoked());
        $this->assertTrue($refreshToken->isRevoked());
    }

    public function testRevokesAllCredentialsForClient(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $em->persist($client = new Client('acme', 'secret'));

        $authCode = $this->buildAuthCode('foo', '+1 minute', $client, 'john');
        $accessToken = $this->buildAccessToken('bar', '+1 minute', $client);
        $refreshToken = $this->buildRefreshToken('baz', '+1 minute', $accessToken);

        $em->persist($authCode);
        $em->persist($accessToken);
        $em->persist($refreshToken);
        $em->flush();

        $revoker = new DoctrineCredentialsRevoker($em);

        $revoker->revokeCredentialsForClient($client);

        $em->refresh($authCode);
        $em->refresh($accessToken);
        $em->refresh($refreshToken);

        $this->assertTrue($authCode->isRevoked());
        $this->assertTrue($accessToken->isRevoked());
        $this->assertTrue($refreshToken->isRevoked());
    }

    private function buildRefreshToken(string $identifier, string $modify, AccessToken $accessToken): RefreshToken
    {
        return new RefreshToken(
            $identifier,
            new DateTimeImmutable($modify),
            $accessToken
        );
    }

    private function buildAccessToken(string $identifier, string $modify, Client $client, ?string $userIdentifier = null): AccessToken
    {
        return new AccessToken(
            $identifier,
            new DateTimeImmutable($modify),
            $client,
            $userIdentifier,
            []
        );
    }

    private function buildAuthCode(string $identifier, string $modify, Client $client, ?string $userIdentifier = null): AuthorizationCode
    {
        return new AuthorizationCode(
            $identifier,
            new DateTimeImmutable($modify),
            $client,
            $userIdentifier,
            []
        );
    }
}
