<?php

declare(strict_types=1);


namespace League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Doctrine\ODM;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

class Driver implements MappingDriver
{
    /**
     * @var string
     */
    private $clientClass;

    /** @var bool */
    private $persistAccessToken;

    /** @var string */
    private $collectionPrefix;

    public function __construct(string $clientClass, bool $persistAccessToken, string $collectionPrefix = 'oauth2_')
    {
        $this->clientClass = $clientClass;
        $this->persistAccessToken = $persistAccessToken;
        $this->collectionPrefix = $collectionPrefix;
    }

    public function loadMetadataForClass($className, PersistenceClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
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

    public function getAllClassNames()
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

    public function isTransient(string $className)
    {
        return false;
    }

    private function buildAbstractClientMetadata(ClassMetadata $metadata)
    {
        $metadata->isMappedSuperclass = true;
        $mapping = [
            'name' => 'name',
            'type' => 'string',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'secret',
            'type' => 'string',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'redirectUris',
            'type' => 'collection',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'grants',
            'type' => 'collection',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'scopes',
            'type' => 'collection',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'active',
            'type' => 'boolean',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'allowPlainTextPkce',
            'type' => 'boolean',
            'options' => [
                'default' => 0,
            ],
        ];
        $metadata->mapField($mapping);
    }

    private function buildAccessTokenMetadata(ClassMetadata $metadata): void
    {
        $metadata->setCollection($this->collectionPrefix . 'access_token');
        $mapping = [
            'name' => 'identifier',
            'type' => 'string',
            'id' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'expiry',
            'type' => 'date_immutable'
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'userIdentifier',
            'type' => 'string',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'scopes',
            'type' => 'collection',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'revoked',
            'type' => 'bool'
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'reference' => true,
            'type' => ClassMetadata::MANY,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'orphanRemoval'    => false,
            'targetDocument' => $this->clientClass,
            'name' => 'client',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL
        ];
        $metadata->mapManyReference($mapping);
    }

    private function buildAuthorizationCodeMetadata(ClassMetadata $metadata)
    {
        $metadata->setCollection($this->collectionPrefix . 'authorization_code');
        $mapping = [
            'name' => 'identifier',
            'type' => 'string',
            "id" => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'expiry',
            'type' => 'datetime_immutable',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'userIdentifier',
            'type' => 'string',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'scopes',
            'type' => 'collection',
            'nullable' => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'revoked',
            'type' => 'bool'
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'reference' => true,
            'type' => ClassMetadata::MANY,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'orphanRemoval'    => false,
            'targetDocument' => $this->clientClass,
            'name' => 'client',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL
        ];
        $metadata->mapManyReference($mapping);
    }

    private function buildClientMetadata(ClassMetadata $metadata): void
    {
        $metadata->setCollection($this->collectionPrefix . 'client');
        $mapping = [
            'name' => 'identifier',
            'type' => 'string',
            "id" => true,
        ];
        $metadata->mapField($mapping);
    }

    private function buildRefreshTokenMetadata(ClassMetadata $metadata): void
    {
        $metadata->setCollection($this->collectionPrefix . 'refresh_token');
        $mapping = [
            'name' => 'identifier',
            'type' => 'string',
            "id" => true,
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'expiry',
            'type' => 'datetime_immutable',
        ];
        $metadata->mapField($mapping);
        $mapping = [
            'name' => 'revoked',
            'type' => 'boolean',
        ];
        $metadata->mapField($mapping);

        if ($this->persistAccessToken) {
            $mapping = [
                'reference' => true,
                'type' => ClassMetadata::MANY,
                'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
                'orphanRemoval'    => false,
                'targetDocument' => AccessToken::class,
                'name' => 'accessToken',
                'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
                'nullable' => true,
            ];
            $metadata->mapManyReference($mapping);
        }
    }

}
