<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

final class OAuth2Events
{
    /**
     * The SCOPE_RESOLVE event occurs right before the user obtains their
     * valid access token.
     *
     * You could alter the access token's scope here.
     */
    public const SCOPE_RESOLVE = 'league.oauth2-server.scope_resolve';

    /**
     * The AUTHORIZATION_REQUEST_RESOLVE event occurs right before the system
     * completes the authorization request.
     *
     * You could approve or deny the authorization request, or set the uri where
     * must be redirected to resolve the authorization request.
     */
    public const AUTHORIZATION_REQUEST_RESOLVE = 'league.oauth2-server.authorization_request_resolve';
}
