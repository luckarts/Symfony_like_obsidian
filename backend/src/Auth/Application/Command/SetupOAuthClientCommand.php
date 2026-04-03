<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:oauth:setup-client',
    description: 'Create or update the default OAuth2 client',
)]
final class SetupOAuthClientCommand extends Command
{
    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        #[Autowire(env: 'OAUTH_DEFAULT_CLIENT_ID')]
        private readonly string $clientId,
        #[Autowire(env: 'OAUTH_DEFAULT_CLIENT_SECRET')]
        private readonly string $clientSecret,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->clientManager->find($this->clientId) !== null) {
            $output->writeln(sprintf("OAuth2 client '%s' already exists.", $this->clientId));

            return Command::SUCCESS;
        }

        $client = new Client(
            name: 'Default Client',
            identifier: $this->clientId,
            secret: $this->clientSecret,
        );

        $client->setGrants(
            new Grant('password'),
            new Grant('client_credentials'),
            new Grant('refresh_token'),
        );

        $client->setScopes(
            new Scope('email'),
            new Scope('profile'),
            new Scope('read'),
            new Scope('write'),
        );

        $this->clientManager->save($client);

        $output->writeln(sprintf("OAuth2 client '%s' created successfully.", $this->clientId));

        return Command::SUCCESS;
    }
}