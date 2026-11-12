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
 */
final readonly class AuthorizationServerMetadataController
{
    /**
     * @param string|null $issuer null when the metadata are not enabled
     * @param string $origin scheme, host and port of the issuer: the endpoints live there, while the issuer itself may carry a path
     * @param array<string, mixed> $metadata the static part of the document, derived from the bundle configuration
     * @param array<string, string> $endpoints metadata key => route name of the endpoints the server offers
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EventDispatcherInterface $eventDispatcher,
        private ?string $issuer,
        private string $origin,
        private array $metadata,
        private array $endpoints,
    ) {
    }

    public function indexAction(): JsonResponse
    {
        if (null === $this->issuer) {
            throw new NotFoundHttpException();
        }

        $metadata = ['issuer' => $this->issuer];

        foreach ($this->endpoints as $key => $route) {
            try {
                $metadata[$key] = $this->absoluteUrl($this->urlGenerator->generate($route, [], UrlGeneratorInterface::ABSOLUTE_PATH));
            } catch (RoutingExceptionInterface) {
                // The application did not import the route of this endpoint, or its route needs parameters the bundle cannot know.
                // Both can be answered with a listener of the metadata event.
            }
        }

        $event = $this->eventDispatcher->dispatch(
            new AuthorizationServerMetadataEvent($metadata + $this->metadata),
            OAuth2Events::AUTHORIZATION_SERVER_METADATA,
        );

        return new JsonResponse($event->getMetadata());
    }

    /**
     * The generator answers with a plain path, unless a host or scheme requirement of the route needs more.
     */
    private function absoluteUrl(string $generated): string
    {
        if (1 === preg_match('#^[a-z][a-z0-9+.-]*://#i', $generated)) {
            return $generated;
        }

        if (str_starts_with($generated, '//')) {
            return strstr($this->origin, ':', true) . ':' . $generated;
        }

        return $this->origin . $generated;
    }
}
