<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Converter;

use League\Bundle\OAuth2ServerBundle\Entity\User;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserConverter implements UserConverterInterface
{
    public const DEFAULT_ANONYMOUS_USER_IDENTIFIER = 'anonymous';

    /** @var non-empty-string */
    private string $anonymousUserIdentifier;

    /**
     * @param non-empty-string $anonymousUserIdentifier
     */
    public function __construct(string $anonymousUserIdentifier = self::DEFAULT_ANONYMOUS_USER_IDENTIFIER)
    {
        $this->anonymousUserIdentifier = $anonymousUserIdentifier;
    }

    /**
     * @psalm-suppress DeprecatedMethod
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function toLeague(?UserInterface $user): UserEntityInterface
    {
        $userEntity = new User();
        if ($user instanceof UserInterface) {
            $identifier = method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername();
            if ('' === $identifier) {
                $identifier = $this->anonymousUserIdentifier;
            }
        } else {
            $identifier = $this->anonymousUserIdentifier;
        }

        $userEntity->setIdentifier($identifier);

        return $userEntity;
    }
}
