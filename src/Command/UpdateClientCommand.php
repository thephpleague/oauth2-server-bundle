<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
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

            ->addOption('add-redirect-uri', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add allowed redirect uri to the client.', [])
            ->addOption('remove-redirect-uri', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Remove allowed redirect uri to the client.', [])

            ->addOption('add-grant-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add allowed grant type to the client.', [])
            ->addOption('remove-grant-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Remove allowed grant type to the client.', [])

            ->addOption('add-scope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add allowed scope to the client.', [])
            ->addOption('remove-scope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Remove allowed scope to the client.', [])

            ->addOption('activate', null, InputOption::VALUE_NONE, 'Activate the client.')
            ->addOption('deactivate', null, InputOption::VALUE_NONE, 'Deactivate the client.')

            ->addArgument('identifier', InputArgument::REQUIRED, 'The client identifier')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $client = $this->clientManager->find($input->getArgument('identifier'))) {
            $io->error(sprintf('OAuth2 client identified as "%s" does not exist.', $input->getArgument('identifier')));

            return 1;
        }

        $client->setActive($this->getClientActiveFromInput($input, $client->isActive()));
        $client->setRedirectUris(...$this->getClientRelatedModelsFromInput($input, RedirectUri::class, $client->getRedirectUris(), 'redirect-uri'));
        $client->setGrants(...$this->getClientRelatedModelsFromInput($input, Grant::class, $client->getGrants(), 'grant-type'));
        $client->setScopes(...$this->getClientRelatedModelsFromInput($input, Scope::class, $client->getScopes(), 'scope'));

        $this->clientManager->save($client);

        $io->success('OAuth2 client updated successfully.');

        return 0;
    }

    private function getClientActiveFromInput(InputInterface $input, bool $actual): bool
    {
        $active = $actual;

        if ($input->getOption('activate') && $input->getOption('deactivate')) {
            throw new \RuntimeException('Cannot specify "--activate" and "--deactivate" at the same time.');
        }

        if ($input->getOption('activate')) {
            $active = true;
        }

        if ($input->getOption('deactivate')) {
            $active = false;
        }

        return $active;
    }

    /**
     * @template T1 of RedirectUri
     * @template T2 of Grant
     * @template T3 of Scope
     *
     * @param class-string<T1>|class-string<T2>|class-string<T3> $modelFqcn
     *
     * @return list<T1>|list<T2>|list<T3>
     *
     * @psalm-suppress UnsafeInstantiation
     */
    private function getClientRelatedModelsFromInput(InputInterface $input, string $modelFqcn, array $actual, string $argument): array
    {
        /** @var list<string> $toAdd */
        $toAdd = $input->getOption($addArgument = sprintf('add-%s', $argument));

        /** @var list<string> $toRemove */
        $toRemove = $input->getOption($removeArgument = sprintf('remove-%s', $argument));

        if ([] !== $colliding = array_intersect($toAdd, $toRemove)) {
            throw new \RuntimeException(sprintf('Cannot specify "%s" in either "--%s" and "--%s".', implode('", "', $colliding), $addArgument, $removeArgument));
        }

        $filtered = array_filter($actual, static function ($model) use ($toRemove): bool {
            return !\in_array((string) $model, $toRemove);
        });

        /** @var list<T1>|list<T2>|list<T3> */
        return array_merge($filtered, array_map(static function (string $value) use ($modelFqcn) {
            return new $modelFqcn($value);
        }, $toAdd));
    }
}
