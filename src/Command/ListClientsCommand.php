<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'league:oauth2-server:list-clients', description: 'Lists existing OAuth2 clients')]
final class ListClientsCommand extends Command
{
    private const ALLOWED_COLUMNS = ['name', 'identifier', 'secret', 'scope', 'redirect uri', 'grant type'];

    public function __construct(
        private readonly ClientManagerInterface $clientManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'columns',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Determine which columns are shown. Can be used multiple times to specify multiple columns.',
                self::ALLOWED_COLUMNS
            )
            ->addOption(
                'redirect-uri',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Finds by redirect uri for client. Use this option multiple times to filter by multiple redirect URIs.',
                []
            )
            ->addOption(
                'grant-type',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Finds by allowed grant type for client. Use this option multiple times to filter by multiple grant types.',
                []
            )
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Finds by allowed scope for client. Use this option multiple times to find by multiple scopes.',
                []
            )
            ->addOption(
                'reveal-secret',
                null,
                InputOption::VALUE_NONE,
                'Reveal the stored client secret in the output. For clients whose secret is hashed, this is the hash, not the original secret.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $criteria = $this->getFindByCriteria($input);
        $clients = $this->clientManager->list($criteria);
        $this->drawTable($input, $output, $clients);

        return 0;
    }

    private function getFindByCriteria(InputInterface $input): ClientFilter
    {
        /** @var list<non-empty-string> $grantStrings */
        $grantStrings = $input->getOption('grant-type');
        /** @var list<non-empty-string> $redirectUriStrings */
        $redirectUriStrings = $input->getOption('redirect-uri');
        /** @var list<non-empty-string> $scopeStrings */
        $scopeStrings = $input->getOption('scope');

        return ClientFilter::create()
            ->addGrantCriteria(...array_map(static fn (string $grant): Grant => new Grant($grant), $grantStrings))
            ->addRedirectUriCriteria(...array_map(static fn (string $redirectUri): RedirectUri => new RedirectUri($redirectUri), $redirectUriStrings))
            ->addScopeCriteria(...array_map(static fn (string $scope): Scope => new Scope($scope), $scopeStrings))
        ;
    }

    /**
     * @param array<ClientInterface> $clients
     */
    private function drawTable(InputInterface $input, OutputInterface $output, array $clients): void
    {
        $io = new SymfonyStyle($input, $output);
        $columns = $this->getColumns($input);
        $rows = $this->getRows($clients, $columns, $input->getOption('reveal-secret'));
        $io->table($columns, $rows);
    }

    /**
     * @param array<ClientInterface> $clients
     * @param array<string> $columns
     *
     * @return array<array<string>>
     */
    private function getRows(array $clients, array $columns, bool $revealSecret): array
    {
        return array_map(static function (ClientInterface $client) use ($columns, $revealSecret): array {
            $values = [
                'name' => $client->getName(),
                'identifier' => $client->getIdentifier(),
                'secret' => $revealSecret ? $client->getSecret() : ($client->isConfidential() ? '****' : '(public)'),
                'scope' => implode(', ', $client->getScopes()),
                'redirect uri' => implode(', ', $client->getRedirectUris()),
                'grant type' => implode(', ', $client->getGrants()),
            ];

            return array_map(static fn (string $column): string => $values[$column] ?? '', $columns);
        }, $clients);
    }

    /**
     * @return array<string>
     */
    private function getColumns(InputInterface $input): array
    {
        $requestedColumns = $input->getOption('columns');
        $requestedColumns = array_map(static fn (string $column): string => strtolower(trim($column)), $requestedColumns);

        return array_intersect($requestedColumns, self::ALLOWED_COLUMNS);
    }
}
