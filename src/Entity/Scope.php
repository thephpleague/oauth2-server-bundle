<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->getIdentifier();
    }
}
