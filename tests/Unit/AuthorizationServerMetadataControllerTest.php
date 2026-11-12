<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Controller\AuthorizationServerMetadataController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AuthorizationServerMetadataControllerTest extends TestCase
{
    public function testItIsNotFoundWhenNoIssuerIsConfigured(): void
    {
        $controller = new AuthorizationServerMetadataController($this->createStub(UrlGeneratorInterface::class), new EventDispatcher(), null, '', [], []);

        $this->expectException(NotFoundHttpException::class);

        $controller->indexAction();
    }

    public function testEndpointsAreBuiltOnTheHostOfTheIssuer(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $route): string => ['oauth2_token' => '/token'][$route]);

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com:8443/auth', 'https://example.com:8443', ['scopes_supported' => ['a']], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(
            ['issuer' => 'https://example.com:8443/auth', 'token_endpoint' => 'https://example.com:8443/token', 'scopes_supported' => ['a']],
            json_decode($controller->indexAction()->getContent(), true),
        );
    }

    #[DataProvider('generatedUrlProvider')]
    public function testARouteRequirementOfTheApplicationIsKept(string $generated, string $expected): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn($generated);

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com/auth', 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame($expected, json_decode($controller->indexAction()->getContent(), true)['token_endpoint']);
    }

    public static function generatedUrlProvider(): iterable
    {
        yield 'a plain path' => ['/token', 'https://example.com/token'];
        yield 'a route with a host requirement' => ['//auth.internal/token', 'https://auth.internal/token'];
        yield 'a route with a scheme requirement' => ['http://example.com/token', 'http://example.com/token'];
    }

    public function testARouteThatNeedsParametersIsLeftOut(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willThrowException(new MissingMandatoryParametersException());

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com', 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(['issuer' => 'https://example.com'], json_decode($controller->indexAction()->getContent(), true));
    }

    public function testAnEndpointWhoseRouteIsNotImportedIsLeftOut(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willThrowException(new RouteNotFoundException());

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com', 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(['issuer' => 'https://example.com'], json_decode($controller->indexAction()->getContent(), true));
    }
}
