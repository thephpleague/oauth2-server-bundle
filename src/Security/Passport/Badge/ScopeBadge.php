<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class ScopeBadge implements BadgeInterface
{
    /**
     * @var bool
     */
    private $resolved = false;

    /**
     * @var list<string>
     */
    private $scopes;

    /**
     * @param list<string> $scopes
     */
    public function __construct(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
    }
}
