<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AuthorizationRequestResolveEventFactory
{
    use AuthorizationRequestResolveEventFactoryTrait;

    public function __construct(ScopeConverterInterface $scopeConverter, ClientManagerInterface $clientManager, Security $security)
    {
        $this->scopeConverter = $scopeConverter;
        $this->clientManager = $clientManager;
        $this->security = $security;
    }
}
