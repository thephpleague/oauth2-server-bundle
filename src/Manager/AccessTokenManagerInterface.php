<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AccessToken;

interface AccessTokenManagerInterface
{
    public function find(string $identifier): ?AccessToken;

    public function save(AccessToken $accessToken): void;

    public function clearExpired(): int;
}
