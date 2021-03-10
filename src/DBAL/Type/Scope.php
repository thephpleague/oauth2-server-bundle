<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;

/**
 * @extends ImplodedArray<ScopeModel>
 */
final class Scope extends ImplodedArray
{
    /**
     * @var string
     */
    private const NAME = 'oauth2_scope';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param list<string> $values
     *
     * @return list<ScopeModel>
     */
    protected function convertDatabaseValues(array $values): array
    {
        return array_map(static function (string $value): ScopeModel {
            return new ScopeModel($value);
        }, $values);
    }
}
