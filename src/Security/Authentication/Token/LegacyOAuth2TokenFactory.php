<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Token;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class LegacyOAuth2TokenFactory
{
    /**
     * @var string
     */
    private $rolePrefix;

    public function __construct(string $rolePrefix)
    {
        $this->rolePrefix = $rolePrefix;
    }

    public function createOAuth2Token(ServerRequestInterface $serverRequest, ?UserInterface $user, string $providerKey): LegacyOAuth2Token
    {
        return new LegacyOAuth2Token($serverRequest, $user, $this->rolePrefix, $providerKey);
    }
}
