<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures;

use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class FakeCredentialsRevoker implements CredentialsRevokerInterface
{
    public function revokeCredentialsForClient(AbstractClient $client): void
    {
    }

    public function revokeCredentialsForUser(UserInterface $user): void
    {
    }
}
