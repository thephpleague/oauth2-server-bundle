<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

interface AccessTokenManagerInterface extends ExpiredTokenClearerInterface
{
    public function find(string $identifier): ?AccessTokenInterface;

    public function save(AccessTokenInterface $accessToken): void;
}
