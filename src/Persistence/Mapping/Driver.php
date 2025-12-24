<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Persistence\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\DeviceCode;
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

    /** @var string */
    private $tablePrefix;

    public function __construct(string $clientClass, bool $persistAccessToken, string $tablePrefix = 'oauth2_')
    {
        $this->clientClass = $clientClass;
        $this->persistAccessToken = $persistAccessToken;
        $this->tablePrefix = $tablePrefix;
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
        if (!$metadata instanceof ORMClassMetadata) {
            throw new \InvalidArgumentException(\sprintf('"$metadata" must be an instance of "%s"', ORMClassMetadata::class));
        }

        switch ($className) {
            case AbstractClient::class:
                $this->buildAbstractClientMetadata($metadata);

                break;
            case AccessToken::class:
                $this->buildAccessTokenMetadata($metadata);

                break;
            case DeviceCode::class:
                $this->buildDeviceCodeMetadata($metadata);

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
                throw new \RuntimeException(\sprintf('%s cannot load metadata for class %s', __CLASS__, $className));
        }
    }

    public function getAllClassNames(): array
    {
        return array_merge(
            [
                AbstractClient::class,
                DeviceCode::class,
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

    /**
     * @param ORMClassMetadata<AbstractClient> $metadata
     */
    private function buildAbstractClientMetadata(ORMClassMetadata $metadata): void
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

    /**
     * @param ORMClassMetadata<AccessToken> $metadata
     */
    private function buildAccessTokenMetadata(ORMClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable($this->tablePrefix . 'access_token')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->createField('userIdentifier', 'string')->length(128)->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('revoked', 'boolean')
            ->createManyToOne('client', $this->clientClass)->addJoinColumn('client', 'identifier', false, false, 'CASCADE')->build()
        ;
    }

    /**
     * @param ORMClassMetadata<AccessToken> $metadata
     */
    private function buildDeviceCodeMetadata(ORMClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable($this->tablePrefix . 'device_code')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->createField('userIdentifier', 'string')->length(128)->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('revoked', 'boolean')
            ->createField('userCode', 'string')->length(255)->nullable(true)->build()
            ->addField('userApproved', 'boolean')
            ->addField('includeVerificationUriComplete', 'boolean')
            ->createField('verificationUri', 'string')->length(255)->nullable(true)->build()
            ->createField('lastPolledAt', 'datetime_immutable')->nullable(true)->build()
            ->createField('interval', 'integer')->columnName('`interval`')->build()
            ->createManyToOne('client', $this->clientClass)->addJoinColumn('client', 'identifier', false, false, 'CASCADE')->build()
        ;
    }

    /**
     * @param ORMClassMetadata<AuthorizationCode> $metadata
     */
    private function buildAuthorizationCodeMetadata(ORMClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable($this->tablePrefix . 'authorization_code')
            ->createField('identifier', 'string')->makePrimaryKey()->length(80)->option('fixed', true)->build()
            ->addField('expiry', 'datetime_immutable')
            ->createField('userIdentifier', 'string')->length(128)->nullable(true)->build()
            ->createField('scopes', 'oauth2_scope')->nullable(true)->build()
            ->addField('revoked', 'boolean')
            ->createManyToOne('client', $this->clientClass)->addJoinColumn('client', 'identifier', false, false, 'CASCADE')->build()
        ;
    }

    /**
     * @param ORMClassMetadata<Client> $metadata
     */
    private function buildClientMetadata(ORMClassMetadata $metadata): void
    {
        (new ClassMetadataBuilder($metadata))
            ->setTable($this->tablePrefix . 'client')
            ->createField('identifier', 'string')->makePrimaryKey()->length(32)->build()
        ;
    }

    /**
     * @param ORMClassMetadata<RefreshToken> $metadata
     */
    private function buildRefreshTokenMetadata(ORMClassMetadata $metadata): void
    {
        $classMetadataBuilder = (new ClassMetadataBuilder($metadata))
            ->setTable($this->tablePrefix . 'refresh_token')
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
