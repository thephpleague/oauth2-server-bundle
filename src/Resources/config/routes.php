<?php

declare(strict_types=1);

trigger_deprecation('league/oauth2-server-bundle', '0.11', 'Loading file "%s" is deprecated. Load "%s" instead.', __FILE__, realpath(__DIR__ . '/../../../config/routes.php'));

return require __DIR__ . '/../../../config/routes.php';
