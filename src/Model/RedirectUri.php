<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

class RedirectUri
{
    /**
     * @var string
     */
    private $redirectUri;

    public function __construct(string $redirectUri)
    {
        if (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException(sprintf('The \'%s\' string is not a valid URI.', $redirectUri));
        }

        $this->redirectUri = $redirectUri;
    }

    public function __toString(): string
    {
        return $this->redirectUri;
    }
}
