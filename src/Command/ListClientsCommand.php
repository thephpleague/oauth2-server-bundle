<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ListClientsCommand extends Command
{
    private const ALLOWED_COLUMNS = ['name', 'identifier', 'secret', 'scope', 'redirect uri', 'grant type'];

    protected static $defaultName = 'league:oauth2-server:list-clients';

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
            ->setDescription('Lists existing oAuth2 clients')
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
        /** @var list<string> $grantStrings */
        $grantStrings = $input->getOption('grant-type');
        /** @var list<string> $redirectUriStrings */
        $redirectUriStrings = $input->getOption('redirect-uri');
        /** @var list<string> $scopeStrings */
        $scopeStrings = $input->getOption('scope');

        return ClientFilter::create()
            ->addGrantCriteria(...array_map(static function (string $grant): Grant {
                return new Grant($grant);
            }, $grantStrings))
            ->addRedirectUriCriteria(...array_map(static function (string $redirectUri): RedirectUri {
                return new RedirectUri($redirectUri);
            }, $redirectUriStrings))
            ->addScopeCriteria(...array_map(static function (string $scope): Scope {
                return new Scope($scope);
            }, $scopeStrings))
        ;
    }

    private function drawTable(InputInterface $input, OutputInterface $output, array $clients): void
    {
        $io = new SymfonyStyle($input, $output);
        $columns = $this->getColumns($input);
        $rows = $this->getRows($clients, $columns);
        $io->table($columns, $rows);
    }

    private function getRows(array $clients, array $columns): array
    {
        return array_map(static function (AbstractClient $client) use ($columns): array {
            $values = [
                'name' => $client->getName(),
                'identifier' => $client->getIdentifier(),
                'secret' => $client->getSecret(),
                'scope' => implode(', ', $client->getScopes()),
                'redirect uri' => implode(', ', $client->getRedirectUris()),
                'grant type' => implode(', ', $client->getGrants()),
            ];

            return array_map(static function (string $column) use ($values): string {
                return $values[$column] ?? '';
            }, $columns);
        }, $clients);
    }

    private function getColumns(InputInterface $input): array
    {
        $requestedColumns = $input->getOption('columns');
        $requestedColumns = array_map(static function (string $column): string {
            return strtolower(trim($column));
        }, $requestedColumns);

        return array_intersect($requestedColumns, self::ALLOWED_COLUMNS);
    }
}
