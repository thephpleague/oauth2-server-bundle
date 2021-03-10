<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

/**
 * @psalm-immutable
 */
class RedirectUri
{
    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @psalm-mutation-free
     */
    public function __construct(string $redirectUri)
    {
        if (!filter_var($redirectUri, \FILTER_VALIDATE_URL)) {
            throw new \RuntimeException(sprintf('The \'%s\' string is not a valid URI.', $redirectUri));
        }

        $this->redirectUri = $redirectUri;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return $this->redirectUri;
    }
}
