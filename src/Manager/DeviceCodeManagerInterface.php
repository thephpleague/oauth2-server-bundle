<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

interface DeviceCodeManagerInterface
{
    public function find(string $identifier): ?DeviceCodeInterface;

    public function findByUserCode(string $code): ?DeviceCodeInterface;

    /**
     * @param bool $persist Set to true when creating a new device code
     */
    public function save(DeviceCodeInterface $deviceCode, bool $persist = true): void;

    public function clearExpired(): int;
}
