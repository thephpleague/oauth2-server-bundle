<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

#[AsCommand(name: 'league:oauth2-server:create-client', description: 'Creates a new OAuth2 client')]
final class CreateClientCommand extends Command
{
    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        private readonly string $clientFqcn,
        private readonly ?PasswordHasherInterface $passwordHasher = null,
    ) {
        parent::__construct();

        if (null === $this->passwordHasher) {
            trigger_deprecation('league/oauth2-server-bundle', '1.2', 'Not passing a "%s" to "%s" is deprecated since version 1.2 and will be required in 2.0.', PasswordHasherInterface::class, self::class);
        }
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
            $plainSecret = $this->resolvePlainSecret($input);
            $client = $this->buildClientFromInput($input, $plainSecret);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        $this->clientManager->save($client);
        $io->success('New OAuth2 client created successfully.');

        $headers = ['Identifier', 'Secret'];
        $rows = [
            [$client->getIdentifier(), $plainSecret],
        ];
        $io->table($headers, $rows);

        return 0;
    }

    private function resolvePlainSecret(InputInterface $input): ?string
    {
        $isPublic = $input->getOption('public');

        if ($isPublic && null !== $input->getArgument('secret')) {
            throw new \InvalidArgumentException('The client cannot have a secret and be public.');
        }

        return $isPublic ? null : $input->getArgument('secret') ?? bin2hex(random_bytes(32));
    }

    private function buildClientFromInput(InputInterface $input, #[\SensitiveParameter] ?string $plainSecret): ClientInterface
    {
        $name = $input->getArgument('name');
        $identifier = (string) $input->getArgument('identifier') ?: hash('md5', random_bytes(16));

        $hashedSecret = $plainSecret;
        if ($this->passwordHasher && \is_string($plainSecret)) {
            $hashedSecret = $this->passwordHasher->hash($plainSecret);
        }

        /** @var AbstractClient $client */
        $client = new $this->clientFqcn($name, $identifier, $hashedSecret);
        $client->setActive(true);
        $client->setAllowPlainTextPkce($input->getOption('allow-plain-text-pkce'));

        /** @var list<non-empty-string> $redirectUriStrings */
        $redirectUriStrings = $input->getOption('redirect-uri');
        /** @var list<non-empty-string> $grantStrings */
        $grantStrings = $input->getOption('grant-type');
        /** @var list<non-empty-string> $scopeStrings */
        $scopeStrings = $input->getOption('scope');

        return $client
            ->setRedirectUris(...array_map(static fn (string $redirectUri): RedirectUri => new RedirectUri($redirectUri), $redirectUriStrings))
            ->setGrants(...array_map(static fn (string $grant): Grant => new Grant($grant), $grantStrings))
            ->setScopes(...array_map(static fn (string $scope): Scope => new Scope($scope), $scopeStrings))
        ;
    }
}
