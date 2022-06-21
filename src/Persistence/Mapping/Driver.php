<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Persistence\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

/**
 * Metadata driver that enables mapping dynamically accordingly to container configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class Driver implements MappingDriver
{
    /**
     * @var string
     */
    private $clientClass;

    /** @var bool */
    private $persistAccessToken;

    public function __construct(string $clientClass, bool $persistAccessToken)
    {
        $this->clientClass = $clientClass;
        $this->persistAccessToken = $persistAccessToken;
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
        switch ($className) {
            case AbstractClient::class:
                $this->buildAbstractClientMetadata($metadata);

                break;
            case AccessToken::class:
                $this->buildAccessTokenMetadata($metadata);

                break;
            case AuthorizationCode::class:
                $this->buildAuthorizationCodeMetadata($metadata);

                break;
            case Client::class:
                $this->buildClientMetadata($metadata);

                break;
            case RefreshToken::class:
                $this->buildRefreshTokenMetadata($metadata);

                break;
            default:
                throw new \RuntimeException(sprintf('%s cannot load metadata for class %s', __CLASS__, $className));
        }
    }

    public function getAllClassNames(): array
    {
        return array_merge(
            [
                AbstractClient::class,
                AuthorizationCode::class,
                RefreshToken::class,
            ],
            Client::class === $this->clientClass ? [Client::class] : [],
            $this->persistAccessToken ? [AccessToken::class] : []
        );
    }

    public function isTransient($className): bool
    {
        return false;
    }

    private function buildAbstractClientMetadata(ClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setMappedSuperClass()
            ->createField('name', 'string')->length(128)->build()
            ->createField('secret', 'string')->length(128)->nullable(true)->build()
            ->createField('redirectUris', 'oauth2_redirect_uri')->nullable(true)->build()
            ->createField('grants', 'oauth2_grant')->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('active', 'boolean')
            ->createField('allowPlainTextPkce', 'boolean')->option('default', 0)->build()
        ;
    }

    private function buildAccessTokenMetadata(ClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable('oauth2_access_token')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->createField('userIdentifier', 'string')->length(128)->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('revoked', 'boolean')
            ->createManyToOne('client', $this->clientClass)->addJoinColumn('client', 'identifier', false, false, 'CASCADE')->build()
        ;
    }

    private function buildAuthorizationCodeMetadata(ClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable('oauth2_authorization_code')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->createField('userIdentifier', 'string')->length(128)->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('revoked', 'boolean')
            ->createManyToOne('client', $this->clientClass)->addJoinColumn('client', 'identifier', false, false, 'CASCADE')->build()
        ;
    }

    private function buildClientMetadata(ClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable('oauth2_client')
            ->createField('identifier', 'string')->makePrimaryKey()->length(32)->build()
        ;
    }

    private function buildRefreshTokenMetadata(ClassMetadata $metadata): void
    {
        $classMetadataBuilder = (new ClassMetadataBuilder($metadata))
            ->setTable('oauth2_refresh_token')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->addField('revoked', 'boolean')
        ;

        if ($this->persistAccessToken) {
            $classMetadataBuilder->createManyToOne('accessToken', AccessToken::class)
                                 ->addJoinColumn('access_token', 'identifier', true, false, 'SET NULL')
                                 ->build()
            ;
        }
    }
}
