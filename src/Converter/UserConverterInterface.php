<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\OAuth2\Server\Entities\UserEntityInterface;

interface UserConverterInterface
{
    public function toLeague(?UserEntityInterface $user): UserEntityInterface;
}
