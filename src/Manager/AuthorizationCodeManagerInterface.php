<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;

interface AuthorizationCodeManagerInterface
{
    public function find(string $identifier): ?AuthorizationCode;

    public function save(AuthorizationCode $authCode): void;

    public function clearExpired(): int;
}
