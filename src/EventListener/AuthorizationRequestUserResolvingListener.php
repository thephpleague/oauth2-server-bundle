<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\EventListener;

use Symfony\Bundle\Security\Core\Security;
use Symfony\Component\Security\Core\Security as LegacySecurity;

if (class_exists(Security::class)) {
    /**
     * Listener sets currently authenticated user to authorization request context
     */
    final class AuthorizationRequestUserResolvingListener
    {
        use AuthorizationRequestUserResolvingListenerTrait;

        /**
         * @var Security
         */
        private $security;

        public function __construct(Security $security)
        {
            $this->security = $security;
        }
    }
} else {
    /**
     * Listener sets currently authenticated user to authorization request context
     */
    final class AuthorizationRequestUserResolvingListener
    {
        use AuthorizationRequestUserResolvingListenerTrait;

        /**
         * @var LegacySecurity
         */
        private $security;

        public function __construct(LegacySecurity $security)
        {
            $this->security = $security;
        }
    }
}
