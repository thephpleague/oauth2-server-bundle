<?php

declare(strict_types=1);

trigger_deprecation('league/oauth2-server-bundle', '0.11', 'Loading resource "@LeagueOAuth2ServerBundle/Resources/config/routes.php" is deprecated. Load "@LeagueOAuth2ServerBundle/config/routes.php" instead.');

return require __DIR__ . '/../../config/routes.php';
