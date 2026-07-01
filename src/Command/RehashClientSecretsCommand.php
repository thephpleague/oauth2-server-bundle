<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

#[AsCommand(name: 'league:oauth2-server:rehash-client-secrets', description: 'Rehashes existing client secrets using the configured password hasher.')]
final class RehashClientSecretsCommand extends Command
{
    private ClientManagerInterface $clientManager;
    private PasswordHasherInterface $hasher;

    public function __construct(ClientManagerInterface $clientManager, PasswordHasherInterface $hasher)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
        $this->hasher = $hasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $migrated = $alreadyHashed = $public = 0;

        foreach ($this->clientManager->list(null) as $client) {
            if (!$client->isConfidential()) {
                ++$public;
                continue;
            }

            $secret = $client->getSecret() ?? '';

            if (!$this->hasher->needsRehash($secret)) {
                ++$alreadyHashed;
                continue;
            }

            $client->setSecret($this->hasher->hash($secret));
            $this->clientManager->save($client);
            ++$migrated;
        }

        $io->success(\sprintf(
            'Migration complete: %d secret(s) rehashed, %d already hashed, %d public client(s) skipped.',
            $migrated,
            $alreadyHashed,
            $public,
        ));

        return Command::SUCCESS;
    }
}
