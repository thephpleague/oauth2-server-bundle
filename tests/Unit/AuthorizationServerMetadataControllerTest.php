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
    public function testEndpointsAreBuiltOnTheHostOfTheIssuer(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $route): string => ['oauth2_token' => '/token'][$route]);

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com:8443/auth', ['scopes_supported' => ['a']], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(
            ['issuer' => 'https://example.com:8443/auth', 'token_endpoint' => 'https://example.com:8443/token', 'scopes_supported' => ['a']],
            json_decode($controller->indexAction('/auth')->getContent(), true),
        );
    }

    public function testUrlsAreNotEscaped(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/token');

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertStringContainsString('"https://example.com/token"', $controller->indexAction()->getContent());
    }

    #[DataProvider('issuerPathProvider')]
    public function testTheDocumentIsOnlyServedAtThePathOfTheIssuer(string $issuer, string $requestedPath, bool $found): void
    {
        $controller = new AuthorizationServerMetadataController($this->createStub(UrlGeneratorInterface::class), new EventDispatcher(), $issuer, [], []);

        if (!$found) {
            $this->expectException(NotFoundHttpException::class);
        }

        $this->assertSame($issuer, json_decode($controller->indexAction($requestedPath)->getContent(), true)['issuer']);
    }

    public static function issuerPathProvider(): iterable
    {
        yield 'an issuer without a path' => ['https://example.com', '', true];
        yield 'an issuer with a trailing slash only' => ['https://example.com/', '', true];
        yield 'an issuer with a path' => ['https://example.com/auth', '/auth', true];
        yield 'an issuer with a nested path and a trailing slash' => ['https://example.com/admin/mcp/', '/admin/mcp', true];
        yield 'a path the issuer does not have' => ['https://example.com', '/auth', false];
        yield 'no path for an issuer that has one' => ['https://example.com/auth', '', false];
        yield 'another path' => ['https://example.com/auth', '/other', false];
        yield 'plain http on localhost' => ['http://localhost:8000', '', true];
        yield 'plain http on an IPv6 loopback' => ['http://[::1]:8000', '', true];
    }

    #[DataProvider('invalidIssuerProvider')]
    public function testAnInvalidIssuerIsAMisconfiguration(string $issuer): void
    {
        $controller = new AuthorizationServerMetadataController($this->createStub(UrlGeneratorInterface::class), new EventDispatcher(), $issuer, [], []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be an https URL');

        $controller->indexAction();
    }

    public static function invalidIssuerProvider(): iterable
    {
        yield 'not a url' => ['example.com'];
        yield 'plain http' => ['http://example.com'];
        yield 'with a query' => ['https://example.com/auth?tenant=1'];
        yield 'with a fragment' => ['https://example.com/auth#top'];
        yield 'with credentials' => ['https://user@example.com'];
        yield 'a malformed url' => ['https:///example.com'];
    }

    #[DataProvider('generatedUrlProvider')]
    public function testARouteRequirementOfTheApplicationIsKept(string $generated, string $expected): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn($generated);

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com/auth', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame($expected, json_decode($controller->indexAction('/auth')->getContent(), true)['token_endpoint']);
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

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(['issuer' => 'https://example.com'], json_decode($controller->indexAction()->getContent(), true));
    }

    public function testAnEndpointWhoseRouteIsNotImportedIsLeftOut(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willThrowException(new RouteNotFoundException());

        $controller = new AuthorizationServerMetadataController($urlGenerator, new EventDispatcher(), 'https://example.com', [], ['token_endpoint' => 'oauth2_token']);

        $this->assertSame(['issuer' => 'https://example.com'], json_decode($controller->indexAction()->getContent(), true));
    }
}
