<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ClearExpiredTokensCommand extends Command
{
    protected static $defaultName = 'league:oauth2-server:clear-expired-tokens';

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

    public function __construct(
        AccessTokenManagerInterface $accessTokenManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        AuthorizationCodeManagerInterface $authorizationCodeManager
    ) {
        parent::__construct();

        $this->accessTokenManager = $accessTokenManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->authorizationCodeManager = $authorizationCodeManager;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $clearExpiredAccessTokens = $input->getOption('access-tokens');
        $clearExpiredRefreshTokens = $input->getOption('refresh-tokens');
        $clearExpiredAuthCodes = $input->getOption('auth-codes');

        if (!$clearExpiredAccessTokens && !$clearExpiredRefreshTokens && !$clearExpiredAuthCodes) {
            $this->clearExpiredAccessTokens($io);
            $this->clearExpiredRefreshTokens($io);
            $this->clearExpiredAuthCodes($io);

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

        return 0;
    }

    private function clearExpiredAccessTokens(SymfonyStyle $io): void
    {
        $numOfClearedAccessTokens = $this->accessTokenManager->clearExpired();
        $io->success(sprintf(
            'Cleared %d expired access token%s.',
            $numOfClearedAccessTokens,
            1 === $numOfClearedAccessTokens ? '' : 's'
        ));
    }

    private function clearExpiredRefreshTokens(SymfonyStyle $io): void
    {
        $numOfClearedRefreshTokens = $this->refreshTokenManager->clearExpired();
        $io->success(sprintf(
            'Cleared %d expired refresh token%s.',
            $numOfClearedRefreshTokens,
            1 === $numOfClearedRefreshTokens ? '' : 's'
        ));
    }

    private function clearExpiredAuthCodes(SymfonyStyle $io): void
    {
        $numOfClearedAuthCodes = $this->authorizationCodeManager->clearExpired();
        $io->success(sprintf(
            'Cleared %d expired auth code%s.',
            $numOfClearedAuthCodes,
            1 === $numOfClearedAuthCodes ? '' : 's'
        ));
    }
}
