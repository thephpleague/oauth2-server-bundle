<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use League\Bundle\OAuth2ServerBundle\LeagueOAuth2ServerBundle;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new DoctrineBundle();
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new LeagueOAuth2ServerBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/.var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/.var/log/' . $this->environment;
    }

    public function process(ContainerBuilder $container): void
    {
        $publicServicesAlias = [
            ScopeManagerInterface::class,
            ClientManagerInterface::class,
            AccessTokenManagerInterface::class,
            RefreshTokenManagerInterface::class,
            AuthorizationCodeManagerInterface::class,
            DeviceCodeManagerInterface::class,
            CredentialsRevokerInterface::class,
        ];

        foreach ($publicServicesAlias as $serviceAlias) {
            $container->getAlias($serviceAlias)->setPublic(true);
        }
    }
}
