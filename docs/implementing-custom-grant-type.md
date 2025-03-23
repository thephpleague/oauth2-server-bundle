# Implementing custom grant type

1. Create a class that implements the `League\OAuth2\Server\Grant\GrantTypeInterface` interface.

   Example:

    ```php
    <?php

    declare(strict_types=1);

    namespace App\Grant;

    use DateInterval;
    use League\OAuth2\Server\Grant\AbstractGrant;
    use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
    use Nyholm\Psr7\Response;
    use Psr\Http\Message\ServerRequestInterface;
    use League\OAuth2\Server\Grant\GrantTypeInterface;

    final class FakeGrant extends AbstractGrant implements GrantTypeInterface
    {
        /**
         * @var SomeDependency
         */
        private $foo;

        public function __construct(SomeDependency $foo)
        {
            $this->foo = $foo;
        }

        public function getIdentifier()
        {
            return 'fake_grant';
        }

        public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, DateInterval $accessTokenTTL)
        {
            return new Response();
        }
    }
    ```

1. In order to enable the new grant type in the authorization server you must register the service in the container.
And the service must be tagged with the `league.oauth2_server.authorization_server.grant` tag:

    ```yaml
    services:

      App\Grant\FakeGrant:
        tags:
          - {name: league.oauth2_server.authorization_server.grant}
    ```

    You could define a custom access token TTL for your grant using `accessTokenTTL` tag attribute :

    ```yaml
    services:

      App\Grant\FakeGrant:
        tags:
          - {name: league.oauth2_server.authorization_server.grant, accessTokenTTL: PT5H}
    ```

    If you prefer php configuration, you could use `AutoconfigureTag` symfony attribute for the same result :

    ```php
   <?php
   ...

   use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

   #[AutoconfigureTag(name: 'league.oauth2_server.authorization_server.grant', attributes: [accessTokenTTL: 'PT5H'])]
   final class FakeGrant extends AbstractGrant implements GrantTypeInterface
   {
       ...
   }
    ```

    If `accessTokenTTL` tag attribute is not defined, then bundle config is used `league_oauth2_server.authorization_server.access_token_ttl` (same as `league.oauth2_server.access_token_ttl.default` service container parameter). \
    `null` is considered as defined, to allow to unset ttl. \
   `league_oauth2_server.authorization_server.refresh_token_ttl` is also accessible for your implementation using `league.oauth2_server.refresh_token_ttl.default` service container parameter.


# Implementing custom grant type (deprecated method)

1. Create a class that implements the `\League\Bundle\OAuth2ServerBundle\League\AuthorizationServer\GrantTypeInterface` interface.

    Example:

    ```php
    <?php

    declare(strict_types=1);

    namespace App\Grant;

    use DateInterval;
    use League\OAuth2\Server\Grant\AbstractGrant;
    use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
    use Nyholm\Psr7\Response;
    use Psr\Http\Message\ServerRequestInterface;
    use League\Bundle\OAuth2ServerBundle\AuthorizationServer\GrantTypeInterface;

    final class FakeGrant extends AbstractGrant implements GrantTypeInterface
    {
        /**
         * @var SomeDependency
         */
        private $foo;

        public function __construct(SomeDependency $foo)
        {
            $this->foo = $foo;
        }

        public function getIdentifier()
        {
            return 'fake_grant';
        }

        public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, DateInterval $accessTokenTTL)
        {
            return new Response();
        }

        public function getAccessTokenTTL(): ?DateInterval
        {
            return new DateInterval('PT5H');
        }
    }
    ```

1. In order to enable the new grant type in the authorization server you must register the service in the container.
The service must be autoconfigured or you have to manually tag it with the `league.oauth2_server.authorization_server.grant` tag:

    ```yaml
    services:
        _defaults:
            autoconfigure: true

        App\Grant\FakeGrant: ~
    ```
