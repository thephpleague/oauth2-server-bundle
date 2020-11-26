<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;

final class Scope extends ImplodedArray
{
    /**
     * @var string
     */
    private const NAME = 'oauth2_scope';

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
            $value = new ScopeModel($value);
        }

        return $values;
    }
}
