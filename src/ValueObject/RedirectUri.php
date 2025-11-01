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
        if (1 !== preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:(?:\/\/[^\/\s?#]+(?:\/[^\s?#]*)?|\/[^\s?#]*)?(?:\?[^\s#]*)?(?:#[^\s]*)?$/', $redirectUri)) {
            throw new \RuntimeException(\sprintf('The \'%s\' string is not a valid URI.', $redirectUri));
        }

        $this->redirectUri = $redirectUri;
    }

    public function __toString(): string
    {
        return $this->redirectUri;
    }
}
