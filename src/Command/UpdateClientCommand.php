<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UpdateClientCommand extends Command
{
    protected static $defaultName = 'league:oauth2-server:update-client';

    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updates an oAuth2 client')
            ->addOption(
                'redirect-uri',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
                []
            )
            ->addOption(
                'grant-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types.',
                []
            )
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed scope for client. Use this option multiple times to set multiple scopes.',
                []
            )
            ->addOption(
                'deactivated',
                null,
                InputOption::VALUE_NONE,
                'If provided, it will deactivate the given client.'
            )
            ->addArgument(
                'identifier',
                InputArgument::REQUIRED,
                'The client ID'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $client = $this->clientManager->find($input->getArgument('identifier'))) {
            $io->error(sprintf('oAuth2 client identified as "%s"', $input->getArgument('identifier')));

            return 1;
        }

        $client = $this->updateClientFromInput($client, $input);
        $this->clientManager->save($client);
        $io->success('Given oAuth2 client updated successfully.');

        return 0;
    }

    private function updateClientFromInput(Client $client, InputInterface $input): Client
    {
        $client->setActive(!$input->getOption('deactivated'));

        $redirectUris = array_map(
            static function (string $redirectUri): RedirectUri { return new RedirectUri($redirectUri); },
            $input->getOption('redirect-uri')
        );
        $client->setRedirectUris(...$redirectUris);

        $grants = array_map(
            static function (string $grant): Grant { return new Grant($grant); },
            $input->getOption('grant-type')
        );
        $client->setGrants(...$grants);

        $scopes = array_map(
            static function (string $scope): Scope { return new Scope($scope); },
            $input->getOption('scope')
        );
        $client->setScopes(...$scopes);

        return $client;
    }
}
