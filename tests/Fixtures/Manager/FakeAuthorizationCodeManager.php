<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Fixtures\Manager;

use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

class FakeAuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return null;
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
    }

    public function clearExpiredTokens(?\DateInterval $expireCleanupDelay = null): int
    {
        return 0;
    }

    public function clearExpired(): int
    {
        return $this->clearExpiredTokens();
    }
}
