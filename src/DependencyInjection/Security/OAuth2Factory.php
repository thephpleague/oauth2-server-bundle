<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;

if (interface_exists(SecurityFactoryInterface::class) && !interface_exists(AuthenticatorFactoryInterface::class)) {
    /**
     * Wires the "oauth" authenticator from user configuration.
     *
     * @author Mathias Arlaud <mathias.arlaud@gmail.com>
     */
    class OAuth2Factory implements SecurityFactoryInterface
    {
        use OAuth2FactoryTrait;
    }
} elseif (!method_exists(SecurityExtension::class, 'addAuthenticatorFactory')) {
    /**
     * Wires the "oauth" authenticator from user configuration.
     *
     * @author Mathias Arlaud <mathias.arlaud@gmail.com>
     */
    class OAuth2Factory implements AuthenticatorFactoryInterface, SecurityFactoryInterface
    {
        use OAuth2FactoryTrait;
    }
} else {
    /**
     * Wires the "oauth" authenticator from user configuration.
     *
     * @author Mathias Arlaud <mathias.arlaud@gmail.com>
     */
    class OAuth2Factory implements AuthenticatorFactoryInterface
    {
        use OAuth2FactoryTrait;
    }
}
