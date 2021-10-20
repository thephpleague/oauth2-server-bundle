<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Security\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;

/** @var \ReflectionNamedType|null $r */
$r = (new \ReflectionMethod(AuthenticatorInterface::class, 'authenticate'))->getReturnType();

if ($r && Passport::class === $r->getName()) {
    /**
     * @internal
     *
     * @psalm-suppress UnrecognizedStatement
     * @psalm-suppress MethodSignatureMismatch
     */
    trait ForwardCompatAuthenticatorTrait
    {
        public function authenticate(Request $request): Passport
        {
            return $this->doAuthenticate($request);
        }
    }
} else {
    /**
     * @internal
     *
     * @psalm-suppress UnrecognizedStatement
     */
    trait ForwardCompatAuthenticatorTrait
    {
        public function authenticate(Request $request): PassportInterface
        {
            return $this->doAuthenticate($request);
        }
    }
}
