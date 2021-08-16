<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * @psalm-template T
 */
abstract class ImplodedArray extends TextType
{
    /**
     * @var string
     */
    private const VALUE_DELIMITER = ' ';

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!\is_array($value)) {
            throw new \LogicException('This type can only be used in combination with arrays.');
        }

        if (0 === \count($value)) {
            return null;
        }

        /** @psalm-var T $item */
        foreach ($value as $item) {
            $this->assertValueCanBeImploded($item);
        }

        return implode(self::VALUE_DELIMITER, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @psalm-return list<T>
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        if (null === $value) {
            return [];
        }

        \assert(\is_string($value), 'Expected $value of be either a string or null.');

        $values = explode(self::VALUE_DELIMITER, $value);

        return $this->convertDatabaseValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 65535;

        return parent::getSQLDeclaration($column, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @psalm-param T $value
     */
    private function assertValueCanBeImploded($value): void
    {
        if (null === $value) {
            return;
        }

        if (is_scalar($value)) {
            return;
        }

        if (\is_object($value) && method_exists($value, '__toString')) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('The value of \'%s\' type cannot be imploded.', \gettype($value)));
    }

    /**
     * @param list<string> $values
     *
     * @return list<T>
     */
    abstract protected function convertDatabaseValues(array $values): array;
}
