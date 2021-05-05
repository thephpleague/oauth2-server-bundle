<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass;

use Defuse\Crypto\Key;
use League\OAuth2\Server\AuthorizationServer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class EncryptionKeyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $encryptionKey = $container->getParameter('league.oauth2_server.encryption_key');
        $encryptionKeyType = $container->resolveEnvPlaceholders($container->getParameter('league.oauth2_server.encryption_key.type'), true);
        $authorizationServer = $container->findDefinition(AuthorizationServer::class);

        if ('plain' === $encryptionKeyType) {
            $authorizationServer->replaceArgument(4, $encryptionKey);

            return;
        }

        if ('defuse' === $encryptionKeyType) {
            if (!class_exists(Key::class)) {
                throw new \RuntimeException('You must install the "defuse/php-encryption" package to use "encryption_key_type: defuse".');
            }

            $keyDefinition = (new Definition(Key::class))
                ->setFactory([Key::class, 'loadFromAsciiSafeString'])
                ->addArgument($encryptionKey);

            $container->setDefinition('league.oauth2_server.defuse_key', $keyDefinition);

            $authorizationServer->replaceArgument(4, new Reference('league.oauth2_server.defuse_key'));

            return;
        }

        throw new \RuntimeException(sprintf('The value "%s" is not allowed for path "league_oauth2_server.authorization_server.encryption_key_type". Permissible values: "plain", "defuse"', $encryptionKeyType));
    }
}
