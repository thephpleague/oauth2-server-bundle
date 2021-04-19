<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\User\UserInterface;

final class OAuth2TokenFactory
{
    /**
     * @var string
     */
    private $rolePrefix;

    public function __construct(string $rolePrefix)
    {
        $this->rolePrefix = $rolePrefix;
    }

    /**
     * @param list<string> $scopes
     */
    public function createOAuth2Token(?UserInterface $user, array $scopes, string $clientId, string $accessTokenId, string $providerKey): OAuth2Token
    {
        return new OAuth2Token($user, $scopes, $clientId, $accessTokenId, $this->rolePrefix, $providerKey);
    }
}
