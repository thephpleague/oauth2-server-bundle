<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class WithAccessTokenTTL
{
    // we don't allow to pass directly \DateInterval, to not break symfony container dumping which require serializable object
    public function __construct(public readonly ?string $accessTokenTTL)
    {
    }
}
