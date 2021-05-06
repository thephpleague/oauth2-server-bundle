<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DeleteClientCommand extends Command
{
    protected static $defaultName = 'league:oauth2-server:delete-client';

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
            ->setDescription('Deletes an OAuth2 client')
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

        $this->clientManager->remove($client);
        $io->success('OAuth2 client deleted successfully.');

        return 0;
    }
}
