<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
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

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updates an OAuth2 client')
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
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets name for client.',
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
            $io->error(sprintf('OAuth2 client identified as "%s" does not exist.', $input->getArgument('identifier')));

            return 1;
        }

        $client = $this->updateClientFromInput($client, $input);
        $this->clientManager->save($client);

        $io->success('OAuth2 client updated successfully.');

        return 0;
    }

    private function updateClientFromInput(AbstractClient $client, InputInterface $input): AbstractClient
    {
        $client->setActive(!$input->getOption('deactivated'));

        /** @var list<string> $redirectUriStrings */
        $redirectUriStrings = $input->getOption('redirect-uri');
        /** @var list<string> $grantStrings */
        $grantStrings = $input->getOption('grant-type');
        /** @var list<string> $scopeStrings */
        $scopeStrings = $input->getOption('scope');

        $client
            ->setRedirectUris(...array_map(static function (string $redirectUri): RedirectUri {
                return new RedirectUri($redirectUri);
            }, $redirectUriStrings))
            ->setGrants(...array_map(static function (string $grant): Grant {
                return new Grant($grant);
            }, $grantStrings))
            ->setScopes(...array_map(static function (string $scope): Scope {
                return new Scope($scope);
            }, $scopeStrings))
        ;

        if ($name = $input->getOption('name')) {
            $client->setName($name);
        }

        return $client;
    }
}
