<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ValueObject;

class RedirectUri
{
    /**
     * @var non-empty-string
     */
    private $redirectUri;

    /**
     * @param non-empty-string $redirectUri
     */
    public function __construct(string $redirectUri)
    {
        if (!filter_var($redirectUri, \FILTER_VALIDATE_URL)) {
            throw new \RuntimeException(\sprintf('The \'%s\' string is not a valid URI.', $redirectUri));
        }

        $this->redirectUri = $redirectUri;
    }

    public function __toString(): string
    {
        return $this->redirectUri;
    }
}
