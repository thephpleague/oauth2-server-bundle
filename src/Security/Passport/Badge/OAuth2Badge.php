<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * BC Layer for 5.1 version
 *
 * This badge only holds data. It is always resolved.
 * To be removed when the minimum supported version of Security component will be 5.2
 * so that OAuth2Authenticator could use Passport::setAttribute instead
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class OAuth2Badge implements BadgeInterface
{
    /**
     * @var string
     */
    private $accessTokenId;

    /**
     * @var list<string>
     */
    private $scopes;

    /**
     * @param list<string> $scopes
     *
     * @psalm-mutation-free
     */
    public function __construct(string $accessTokenId, array $scopes)
    {
        $this->accessTokenId = $accessTokenId;
        $this->scopes = $scopes;
    }

    /**
     * @psalm-mutation-free
     */
    public function getAccessTokenId(): string
    {
        return $this->accessTokenId;
    }

    /**
     * @return list<string>
     *
     * @psalm-mutation-free
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
