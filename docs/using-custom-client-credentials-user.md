# Using custom client credentials user

By default, access tokens issued with the `client_credentials` grant authenticate as
`ClientCredentialsUser`. To provide your own user class, implement
`League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUserFactoryInterface` in a
service. Its `createUser(string $clientId): UserInterface` method receives the client ID
and must return the user to authenticate. Set your custom factory as the service for
`League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUserFactoryInterface`.

```yaml
# config/services.yaml
services:
    App\Security\CustomClientCredentialsUserFactory: ~
    League\Bundle\OAuth2ServerBundle\Security\User\ClientCredentialsUserFactoryInterface: '@App\Security\CustomClientCredentialsUserFactory'
```

The custom factory is used by every `oauth2` firewall. Tokens associated with a user
continue to be resolved through the configured user provider.