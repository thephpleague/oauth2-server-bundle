<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator;
use League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUser;
use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Pins that the two security classes applications need to specialize stay extendable.
 *
 * The subclasses below are declared at file scope, so this test file cannot even be
 * autoloaded if either parent is `final` — the failure is a compile-time fatal, not an
 * assertion. That is deliberate: it is the same breakage a downstream application hits.
 *
 * `instanceof` is what is actually being protected. Composition is not an equivalent
 * workaround for these two, because a wrapper fails every type check the framework and
 * the surrounding application perform against the vendor class.
 */
final class SecurityClassExtensibilityTest extends TestCase
{
    public function testClientCredentialsUserCanBeSpecialized(): void
    {
        $user = new ExtendedClientCredentialsUser('client-id', 'tenant-7');

        $this->assertInstanceOf(ClientCredentialsUser::class, $user);
        $this->assertSame('client-id', $user->getUserIdentifier());
        $this->assertSame([], $user->getRoles());
        $this->assertSame('tenant-7', $user->getTenant());
    }

    public function testOAuth2AuthenticatorCanBeSpecialized(): void
    {
        $authenticator = new ExtendedOAuth2Authenticator(
            $this->createMock(HttpMessageFactoryInterface::class),
            $this->createMock(ResourceServer::class),
            $this->createMock(UserProviderInterface::class),
            'PREFIX_'
        );

        $this->assertInstanceOf(OAuth2Authenticator::class, $authenticator);
    }
}

class ExtendedClientCredentialsUser extends ClientCredentialsUser
{
    public function __construct(
        string $clientId,
        private readonly string $tenant,
    ) {
        parent::__construct($clientId);
    }

    public function getTenant(): string
    {
        return $this->tenant;
    }
}

class ExtendedOAuth2Authenticator extends OAuth2Authenticator
{
}
