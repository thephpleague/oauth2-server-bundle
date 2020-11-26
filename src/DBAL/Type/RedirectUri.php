<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use League\Bundle\OAuth2ServerBundle\Model\RedirectUri as RedirectUriModel;

final class RedirectUri extends ImplodedArray
{
    /**
     * @var string
     */
    private const NAME = 'oauth2_redirect_uri';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDatabaseValues(array $values): array
    {
        foreach ($values as &$value) {
            $value = new RedirectUriModel($value);
        }

        return $values;
    }
}
