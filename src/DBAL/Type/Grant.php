<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use League\Bundle\OAuth2ServerBundle\ValueObject\Grant as GrantModel;

/**
 * @extends ImplodedArray<GrantModel>
 */
final class Grant extends ImplodedArray
{
    /**
     * @var string
     */
    private const NAME = 'oauth2_grant';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param list<string> $values
     *
     * @return list<GrantModel>
     */
    protected function convertDatabaseValues(array $values): array
    {
        return array_map(static function (string $value): GrantModel {
            return new GrantModel($value);
        }, $values);
    }
}
