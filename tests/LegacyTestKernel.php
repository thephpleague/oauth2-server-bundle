<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests;

final class LegacyTestKernel extends TestKernel
{
    protected $useLegacySecuritySystem = true;
}
