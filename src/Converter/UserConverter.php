<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\Bundle\OAuth2ServerBundle\Entity\User;
use League\OAuth2\Server\Entities\UserEntityInterface;

final class UserConverter implements UserConverterInterface
{
    /**
     * @psalm-suppress DeprecatedMethod
     */
    public function toLeague(?UserEntityInterface $user): UserEntityInterface
    {
        $userEntity = new User();
        if ($user instanceof UserEntityInterface) {
            $userEntity->setIdentifier($user->getIdentifier());
        }

        return $userEntity;
    }
}
