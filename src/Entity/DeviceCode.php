<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Entity;

use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\DeviceCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

final class DeviceCode implements DeviceCodeEntityInterface
{
    use DeviceCodeTrait;
    use EntityTrait;
    use TokenEntityTrait;
}
