<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Controller;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationServerMetadataEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Serves the RFC 8414 authorization server metadata document.
 *
 * @internal
 */
final readonly class AuthorizationServerMetadataController
{
    private const LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    /**
     * @param string $issuer an https URL without query or fragment, validated here because it may be an environment variable
     * @param array<string, mixed> $metadata the static part of the document, derived from the bundle configuration
     * @param array<string, string> $endpoints metadata key => route name of the endpoints the server offers
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EventDispatcherInterface $eventDispatcher,
        private string $issuer,
        private array $metadata,
        private array $endpoints,
    ) {
    }

    /**
     * @param string $issuerPath the part of the path after the well-known segment, it must be the path of the issuer
     */
    public function indexAction(string $issuerPath = ''): JsonResponse
    {
        $parts = $this->parseIssuer();

        if (rtrim($parts['path'] ?? '', '/') !== $issuerPath) {
            throw new NotFoundHttpException();
        }

        $origin = \sprintf('%s://%s%s', strtolower($parts['scheme']), strtolower($parts['host']), isset($parts['port']) ? ':' . $parts['port'] : '');
        $metadata = ['issuer' => $this->issuer];

        foreach ($this->endpoints as $key => $route) {
            try {
                $metadata[$key] = $this->absoluteUrl($this->urlGenerator->generate($route, [], UrlGeneratorInterface::ABSOLUTE_PATH), $origin);
            } catch (RoutingExceptionInterface) {
                // The application did not import the route of this endpoint, or its route needs parameters the bundle cannot know.
                // Both can be answered with a listener of the metadata event.
            }
        }

        $event = $this->eventDispatcher->dispatch(
            new AuthorizationServerMetadataEvent($metadata + $this->metadata),
            OAuth2Events::AUTHORIZATION_SERVER_METADATA,
        );

        return (new JsonResponse($event->getMetadata()))->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array{scheme: string, host: string, path?: string, port?: int}
     */
    private function parseIssuer(): array
    {
        $parts = parse_url($this->issuer);

        $isValid = \is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && !isset($parts['query']) && !isset($parts['fragment']) && !isset($parts['user']) && !isset($parts['pass'])
            && ('https' === strtolower($parts['scheme']) || ('http' === strtolower($parts['scheme']) && \in_array(strtolower($parts['host']), self::LOOPBACK_HOSTS, true)));

        if (!$isValid) {
            throw new \LogicException('The "issuer" of "league_oauth2_server.authorization_server.metadata" must be an https URL without query, fragment or credentials. Plain http is only accepted for localhost, 127.0.0.1 and [::1].');
        }

        return $parts;
    }

    /**
     * The generator answers with a plain path, unless a host or scheme requirement of the route needs more.
     */
    private function absoluteUrl(string $generated, string $origin): string
    {
        if (1 === preg_match('#^[a-z][a-z0-9+.-]*://#i', $generated)) {
            return $generated;
        }

        if (str_starts_with($generated, '//')) {
            return strstr($origin, ':', true) . ':' . $generated;
        }

        return $origin . $generated;
    }
}
