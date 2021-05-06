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

final class CreateClientCommand extends Command
{
    protected static $defaultName = 'league:oauth2-server:create-client';

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var string
     */
    private $clientFqcn;

    public function __construct(ClientManagerInterface $clientManager, string $clientFqcn)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
        $this->clientFqcn = $clientFqcn;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new OAuth2 client')
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
                'public',
                null,
                InputOption::VALUE_NONE,
                'Create a public client.'
            )
            ->addOption(
                'allow-plain-text-pkce',
                null,
                InputOption::VALUE_NONE,
                'Create a client who is allowed to use plain challenge method for PKCE.'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The client name'
            )
            ->addArgument(
                'identifier',
                InputArgument::OPTIONAL,
                'The client identifier'
            )
            ->addArgument(
                'secret',
                InputArgument::OPTIONAL,
                'The client secret'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $client = $this->buildClientFromInput($input);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        $this->clientManager->save($client);
        $io->success('New OAuth2 client created successfully.');

        $headers = ['Identifier', 'Secret'];
        $rows = [
            [$client->getIdentifier(), $client->getSecret()],
        ];
        $io->table($headers, $rows);

        return 0;
    }

    private function buildClientFromInput(InputInterface $input): AbstractClient
    {
        $name = $input->getArgument('name');

        /** @var string $identifier */
        $identifier = $input->getArgument('identifier') ?? hash('md5', random_bytes(16));

        $isPublic = $input->getOption('public');

        if ($isPublic && null !== $input->getArgument('secret')) {
            throw new \InvalidArgumentException('The client cannot have a secret and be public.');
        }

        /** @var string $secret */
        $secret = $isPublic ? null : $input->getArgument('secret') ?? hash('sha512', random_bytes(32));

        /** @var AbstractClient $client */
        $client = new $this->clientFqcn($name, $identifier, $secret);
        $client->setActive(true);
        $client->setAllowPlainTextPkce($input->getOption('allow-plain-text-pkce'));

        /** @var list<string> $redirectUriStrings */
        $redirectUriStrings = $input->getOption('redirect-uri');
        /** @var list<string> $grantStrings */
        $grantStrings = $input->getOption('grant-type');
        /** @var list<string> $scopeStrings */
        $scopeStrings = $input->getOption('scope');

        return $client
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
    }
}
