<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class AuthorizationServerMetadataEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private array $metadata,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }
}
