<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class ScopeBadge implements BadgeInterface
{
    private bool $resolved = false;

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly array $scopes,
    ) {
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
