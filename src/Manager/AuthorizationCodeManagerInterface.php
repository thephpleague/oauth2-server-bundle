<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

interface AuthorizationCodeManagerInterface
{
    public function find(string $identifier): ?AuthorizationCodeInterface;

    public function save(AuthorizationCodeInterface $authCode): void;

    public function clearExpired(): int;
}
