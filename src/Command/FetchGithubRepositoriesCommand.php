<?php

namespace App\Command;

use App\Actions\RefreshGithubRepositoriesAction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:github:fetch-repositories',
    description: 'Fetch Github Repositories Via REST API',
)]
class FetchGithubRepositoriesCommand extends Command
{
    public function __construct(
        private readonly RefreshGithubRepositoriesAction $action
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('per_page', 'p', InputOption::VALUE_OPTIONAL, 'Result set per page total', 100)
            ->addOption('max_result', 'm', InputOption::VALUE_OPTIONAL, 'Max result set less then 1000', 500)
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('Fetching Github Repositories...');
        $this->action->execute(
            per_page: $input->getOption('per_page'),
            max_result: $input->getOption('max_result'),
        );

        $io->success('Fetch has started as a background jobs.');

        return Command::SUCCESS;
    }
}
