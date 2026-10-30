<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\DeviceCodeInterface;

interface DeviceCodeManagerInterface extends ExpiredTokenClearerInterface
{
    public function find(string $identifier): ?DeviceCodeInterface;

    public function findByUserCode(string $code): ?DeviceCodeInterface;

    public function save(DeviceCodeInterface $deviceCode): void;
}
