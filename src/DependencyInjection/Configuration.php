<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection;

use Defuse\Crypto\Key;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('league_oauth2_server');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->append($this->createAuthorizationServerNode());
        $rootNode->append($this->createResourceServerNode());
        $rootNode->append($this->createScopesNode());
        $rootNode->append($this->createPersistenceNode());
        $rootNode->append($this->createClientNode());

        $rootNode
            ->children()
                ->scalarNode('role_prefix')
                    ->info('Set a custom prefix that replaces the default \'ROLE_OAUTH2_\' role prefix')
                    ->defaultValue('ROLE_OAUTH2_')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function createAuthorizationServerNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('authorization_server');
        $node = $treeBuilder->getRootNode();

        $node
            ->isRequired()
            ->children()
                ->scalarNode('private_key')
                    ->info("Full path to the private key file.\nHow to generate a private key: https://oauth2.thephpleague.com/installation/#generating-public-and-private-keys")
                    ->example('/var/oauth/private.key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('private_key_passphrase')
                    ->info('Passphrase of the private key, if any')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('encryption_key')
                    ->info(sprintf("The plain string or the ascii safe string used to create a %s to be used as an encryption key.\nHow to generate an encryption key: https://oauth2.thephpleague.com/installation/#string-password", Key::class))
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('encryption_key_type')
                    ->info("The type of value of 'encryption_key'\nShould be either 'plain' or 'defuse'")
                    ->cannotBeEmpty()
                    ->defaultValue('plain')
                ->end()
                ->scalarNode('access_token_ttl')
                    ->info("How long the issued access token should be valid for.\nThe value should be a valid interval: http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters")
                    ->cannotBeEmpty()
                    ->defaultValue('PT1H')
                ->end()
                ->scalarNode('refresh_token_ttl')
                    ->info("How long the issued refresh token should be valid for.\nThe value should be a valid interval: http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters")
                    ->cannotBeEmpty()
                    ->defaultValue('P1M')
                ->end()
                ->scalarNode('auth_code_ttl')
                    ->info("How long the issued auth code should be valid for.\nThe value should be a valid interval: http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters")
                    ->cannotBeEmpty()
                    ->defaultValue('PT10M')
                ->end()
                ->booleanNode('enable_client_credentials_grant')
                    ->info('Whether to enable the client credentials grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enable_password_grant')
                    ->info('Whether to enable the password grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enable_refresh_token_grant')
                    ->info('Whether to enable the refresh token grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enable_auth_code_grant')
                    ->info('Whether to enable the authorization code grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('require_code_challenge_for_public_clients')
                    ->info('Whether to require code challenge for public clients for the auth code grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enable_implicit_grant')
                    ->info('Whether to enable the implicit grant')
                    ->defaultTrue()
                ->end()
                ->booleanNode('persist_access_token')
                    ->info('Whether to enable access token saving to persistence layer')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function createResourceServerNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('resource_server');
        $node = $treeBuilder->getRootNode();

        $node
            ->isRequired()
            ->children()
                ->scalarNode('public_key')
                    ->info("Full path to the public key file\nHow to generate a public key: https://oauth2.thephpleague.com/installation/#generating-public-and-private-keys")
                    ->example('/var/oauth/public.key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function createScopesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('scopes');
        $node = $treeBuilder->getRootNode();

        $node
            ->isRequired()
            ->children()
                ->arrayNode('available')
                    ->info("Scopes that you wish to utilize in your application.\nThis should be a simple array of strings.")
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('default')
                    ->info("Scopes that will be assigned when no scope given.\nThis should be a simple array of strings.")
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function createPersistenceNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('persistence');
        $node = $treeBuilder->getRootNode();

        $node
            ->info("Configures different persistence methods that can be used by the bundle for saving client and token data.\nOnly one persistence method can be configured at a time.")
            ->isRequired()
            ->performNoDeepMerging()
            ->children()
                // Doctrine persistence
                ->arrayNode('doctrine')
                    ->children()
                        ->scalarNode('entity_manager')
                            ->info('Name of the entity manager that you wish to use for managing clients and tokens.')
                            ->cannotBeEmpty()
                            ->defaultValue('default')
                        ->end()
                    ->end()
                ->end()
                // In-memory persistence
                ->scalarNode('in_memory')
                ->end()
            ->end()
        ;

        return $node;
    }

    private function createClientNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('client');
        $node = $treeBuilder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('classname')
                    ->info(sprintf('Set a custom client class. Must be a %s', AbstractClient::class))
                    ->defaultValue(Client::class)
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !is_a($v, AbstractClient::class, true);
                        })
                        ->thenInvalid(sprintf('%%s must be a %s', AbstractClient::class))
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
