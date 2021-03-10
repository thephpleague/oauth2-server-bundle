<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use League\Bundle\OAuth2ServerBundle\Model\RedirectUri as RedirectUriModel;

/**
 * @template-extends ImplodedArray<RedirectUriModel>
 */
final class RedirectUri extends ImplodedArray
{
    /**
     * @var string
     */
    private const NAME = 'oauth2_redirect_uri';

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
     * @return list<RedirectUriModel>
     */
    protected function convertDatabaseValues(array $values): array
    {
        return array_map(static function (string $value): RedirectUriModel {
            return new RedirectUriModel($value);
        }, $values);
    }
}
