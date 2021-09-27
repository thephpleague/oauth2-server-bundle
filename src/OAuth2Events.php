<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

final class OAuth2Events
{
    /**
     * The USER_RESOLVE event occurs when the client requests a "password"
     * grant type from the authorization server.
     *
     * You should set a valid user here if applicable.
     */
    public const USER_RESOLVE = 'league.oauth2_server.event.user_resolve';

    /**
     * The SCOPE_RESOLVE event occurs right before the user obtains their
     * valid access token.
     *
     * You could alter the access token's scope here.
     */
    public const SCOPE_RESOLVE = 'league.oauth2_server.event.scope_resolve';

    /**
     * The AUTHORIZATION_REQUEST_RESOLVE event occurs right before the system
     * complete the authorization request.
     *
     * You could approve or deny the authorization request, or set the uri where
     * must be redirected to resolve the authorization request.
     */
    public const AUTHORIZATION_REQUEST_RESOLVE = 'league.oauth2_server.event.authorization_request_resolve';

    /**
     * The REQUEST_TOKEN_RESOLVE event occurs right before the system
     * complete token request.
     *
     * You could manipulate the response.
     */
    public const TOKEN_REQUEST_RESOLVE = 'league.oauth2_server.event.token_request_resolve';

    /**
     * The PRE_SAVE_CLIENT event occurs right before the client is saved
     * by a ClientManager.
     *
     * You could alter the client here.
     */
    public const PRE_SAVE_CLIENT = 'league.oauth2_server.event.pre_save_client';
}
