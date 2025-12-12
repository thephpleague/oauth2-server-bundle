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
    /**
     * @var AccessTokenManagerInterface
     */
    private $accessTokenManager;

    /**
     * @var RefreshTokenManagerInterface
     */
    private $refreshTokenManager;

    /**
     * @var AuthorizationCodeManagerInterface
     */
    private $authorizationCodeManager;

    /**
     * @var DeviceCodeManagerInterface
     */
    private $deviceCodeManager;

    public function __construct(
        AccessTokenManagerInterface $accessTokenManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        AuthorizationCodeManagerInterface $authorizationCodeManager,
        DeviceCodeManagerInterface $deviceCodeManager,
    ) {
        parent::__construct();

        $this->accessTokenManager = $accessTokenManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->authorizationCodeManager = $authorizationCodeManager;
        $this->deviceCodeManager = $deviceCodeManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clears all expired access and/or refresh tokens and/or auth codes')
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $clearExpiredAccessTokens = $input->getOption('access-tokens');
        $clearExpiredRefreshTokens = $input->getOption('refresh-tokens');
        $clearExpiredAuthCodes = $input->getOption('auth-codes');
        $clearExpiredDeviceCodes = $input->getOption('device-codes');

        if (!$clearExpiredAccessTokens && !$clearExpiredRefreshTokens && !$clearExpiredAuthCodes && !$clearExpiredDeviceCodes) {
            $this->clearExpiredAccessTokens($io);
            $this->clearExpiredRefreshTokens($io);
            $this->clearExpiredAuthCodes($io);
            $this->clearExpiredDeviceCodes($io);

            return 0;
        }

        if ($clearExpiredAccessTokens) {
            $this->clearExpiredAccessTokens($io);
        }

        if ($clearExpiredRefreshTokens) {
            $this->clearExpiredRefreshTokens($io);
        }

        if ($clearExpiredAuthCodes) {
            $this->clearExpiredAuthCodes($io);
        }

        if ($clearExpiredDeviceCodes) {
            $this->clearExpiredDeviceCodes($io);
        }

        return 0;
    }

    private function clearExpiredAccessTokens(SymfonyStyle $io): void
    {
        $numOfClearedAccessTokens = $this->accessTokenManager->clearExpired();
        $io->success(\sprintf(
            'Cleared %d expired access token%s.',
            $numOfClearedAccessTokens,
            1 === $numOfClearedAccessTokens ? '' : 's'
        ));
    }

    private function clearExpiredRefreshTokens(SymfonyStyle $io): void
    {
        $numOfClearedRefreshTokens = $this->refreshTokenManager->clearExpired();
        $io->success(\sprintf(
            'Cleared %d expired refresh token%s.',
            $numOfClearedRefreshTokens,
            1 === $numOfClearedRefreshTokens ? '' : 's'
        ));
    }

    private function clearExpiredAuthCodes(SymfonyStyle $io): void
    {
        $numOfClearedAuthCodes = $this->authorizationCodeManager->clearExpired();
        $io->success(\sprintf(
            'Cleared %d expired auth code%s.',
            $numOfClearedAuthCodes,
            1 === $numOfClearedAuthCodes ? '' : 's'
        ));
    }

    private function clearExpiredDeviceCodes(SymfonyStyle $io): void
    {
        $numberOfClearedDeviceCodes = $this->deviceCodeManager->clearExpired();
        $io->success(\sprintf(
            'Cleared %d expired device code%s.',
            $numberOfClearedDeviceCodes,
            1 === $numberOfClearedDeviceCodes ? '' : 's'
        ));
    }
}
