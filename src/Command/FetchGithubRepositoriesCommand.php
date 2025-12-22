<?php

namespace App\Command;

use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
        private readonly GithubAPIServiceInterface $githubAPIService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        //        $this
        //            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        //        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        //        $arg1 = $input->getArgument('arg1');
        //
        //        if ($arg1) {
        //            $io->note(sprintf('You passed an argument: %s', $arg1));
        //        }
        //
        //        if ($input->getOption('option1')) {
        //            // ...
        //        }
       // $data = $this->githubAPIService->fetchRepository('danielmiessler', 'SecLists');
        $queryParam = RepositorySearchParamsDTO::fromArray(
            [
                'language' => 'PHP',
                'sort' => 'stars',
            ]
        );
        foreach ( $this->githubAPIService->fetchAllRepositories($queryParam) as $repository) {
            $io->writeln($repository->data['full_name']. ", ". $repository->data['stargazers_count']);
        }


        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
