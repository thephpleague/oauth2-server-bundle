<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'league:oauth2-server:clear-expired-tokens', description: 'Clears all expired access and/or refresh tokens and/or auth codes')]
final class ClearExpiredTokensCommand extends Command
{
    public function __construct(
        private readonly AccessTokenManagerInterface $accessTokenManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AuthorizationCodeManagerInterface $authorizationCodeManager,
        private readonly DeviceCodeManagerInterface $deviceCodeManager,
        private readonly bool $enableDeviceCodeGrant = true,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'access-tokens',
                'a',
                InputOption::VALUE_NONE,
                'Clear expired access tokens.'
            )
            ->addOption(
                'refresh-tokens',
                'r',
                InputOption::VALUE_NONE,
                'Clear expired refresh tokens.'
            )
            ->addOption(
                'auth-codes',
                'c',
                InputOption::VALUE_NONE,
                'Clear expired auth codes.'
            )
            ->addOption(
                'device-codes',
                'dc',
                InputOption::VALUE_NONE,
                'Clear expired device codes.'
            )
            ->addOption(
                'expire-cleanup-delay',
                null,
                InputOption::VALUE_OPTIONAL,
                'The expiration grace period determines how long expired values are retained before being removed. It accepts either a "DateInterval"-compatible string (e.g., "PT5S" for 5 seconds) or a human-readable duration (e.g., "5 seconds").'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $clearExpiredAccessTokens = $input->getOption('access-tokens');
        $clearExpiredRefreshTokens = $input->getOption('refresh-tokens');
        $clearExpiredAuthCodes = $input->getOption('auth-codes');
        $clearExpiredDeviceCodes = $input->getOption('device-codes');

        if (null !== ($expireCleanupDelay = $input->getOption('expire-cleanup-delay'))) {
            try {
                $expireCleanupDelay = str_starts_with($expireCleanupDelay, 'P') ? new \DateInterval($expireCleanupDelay) : \DateInterval::createFromDateString($expireCleanupDelay);
            } catch (\Throwable $e) {
                $io->error(\sprintf('Invalid delay format: %s', $e->getMessage()));

                return Command::FAILURE;
            }
        }

        if (!$clearExpiredAccessTokens && !$clearExpiredRefreshTokens && !$clearExpiredAuthCodes && !$clearExpiredDeviceCodes) {
            $this->clearExpiredAccessTokens($io, $expireCleanupDelay);
            $this->clearExpiredRefreshTokens($io, $expireCleanupDelay);
            $this->clearExpiredAuthCodes($io, $expireCleanupDelay);
            if ($this->enableDeviceCodeGrant) {
                $this->clearExpiredDeviceCodes($io, $expireCleanupDelay);
            }

            return Command::SUCCESS;
        }

        if ($clearExpiredAccessTokens) {
            $this->clearExpiredAccessTokens($io, $expireCleanupDelay);
        }

        if ($clearExpiredRefreshTokens) {
            $this->clearExpiredRefreshTokens($io, $expireCleanupDelay);
        }

        if ($clearExpiredAuthCodes) {
            $this->clearExpiredAuthCodes($io, $expireCleanupDelay);
        }

        if ($clearExpiredDeviceCodes) {
            $this->clearExpiredDeviceCodes($io, $expireCleanupDelay);
        }

        return Command::SUCCESS;
    }

    private function clearExpiredAccessTokens(SymfonyStyle $io, ?\DateInterval $expireCleanupDelay = null): void
    {
        if (!method_exists($this->accessTokenManager, 'clearExpiredTokens')) {
            trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Not implementing method "clearExpiredTokens()" in "%s" is deprecated. This method will be required in 3.0.', $this->accessTokenManager::class);
            $numOfClearedAccessTokens = $this->accessTokenManager->clearExpired();
        } else {
            $numOfClearedAccessTokens = $this->accessTokenManager->clearExpiredTokens($expireCleanupDelay);
        }
        $io->success(\sprintf(
            'Cleared %d expired access token%s.',
            $numOfClearedAccessTokens,
            1 === $numOfClearedAccessTokens ? '' : 's'
        ));
    }

    private function clearExpiredRefreshTokens(SymfonyStyle $io, ?\DateInterval $expireCleanupDelay = null): void
    {
        if (!method_exists($this->refreshTokenManager, 'clearExpiredTokens')) {
            trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Not implementing method "clearExpiredTokens()" in "%s" is deprecated. This method will be required in 3.0.', $this->refreshTokenManager::class);
            $numOfClearedRefreshTokens = $this->refreshTokenManager->clearExpired();
        } else {
            $numOfClearedRefreshTokens = $this->refreshTokenManager->clearExpiredTokens($expireCleanupDelay);
        }
        $io->success(\sprintf(
            'Cleared %d expired refresh token%s.',
            $numOfClearedRefreshTokens,
            1 === $numOfClearedRefreshTokens ? '' : 's'
        ));
    }

    private function clearExpiredAuthCodes(SymfonyStyle $io, ?\DateInterval $expireCleanupDelay = null): void
    {
        if (!method_exists($this->authorizationCodeManager, 'clearExpiredTokens')) {
            trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Not implementing method "clearExpiredTokens()" in "%s" is deprecated. This method will be required in 3.0.', $this->authorizationCodeManager::class);
            $numOfClearedAuthCodes = $this->authorizationCodeManager->clearExpired();
        } else {
            $numOfClearedAuthCodes = $this->authorizationCodeManager->clearExpiredTokens($expireCleanupDelay);
        }
        $io->success(\sprintf(
            'Cleared %d expired auth code%s.',
            $numOfClearedAuthCodes,
            1 === $numOfClearedAuthCodes ? '' : 's'
        ));
    }

    private function clearExpiredDeviceCodes(SymfonyStyle $io, ?\DateInterval $expireCleanupDelay = null): void
    {
        if (!method_exists($this->deviceCodeManager, 'clearExpiredTokens')) {
            trigger_deprecation('league/oauth2-server-bundle', '2.1', 'Not implementing method "clearExpiredTokens()" in "%s" is deprecated. This method will be required in 3.0.', $this->deviceCodeManager::class);
            $numberOfClearedDeviceCodes = $this->deviceCodeManager->clearExpired();
        } else {
            $numberOfClearedDeviceCodes = $this->deviceCodeManager->clearExpiredTokens($expireCleanupDelay);
        }
        $io->success(\sprintf(
            'Cleared %d expired device code%s.',
            $numberOfClearedDeviceCodes,
            1 === $numberOfClearedDeviceCodes ? '' : 's'
        ));
    }
}
