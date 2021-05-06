<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Driver;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class RegisterDoctrineOrmMappingPass extends DoctrineOrmMappingsPass
{
    public function __construct()
    {
        parent::__construct(
            new Reference(Driver::class),
            ['League\Bundle\OAuth2ServerBundle\Model'],
            ['league.oauth2_server.persistence.doctrine.manager'],
            'league.oauth2_server.persistence.doctrine.enabled',
            ['LeagueOAuth2ServerBundle' => 'League\Bundle\OAuth2ServerBundle\Model']
        );
    }
}
