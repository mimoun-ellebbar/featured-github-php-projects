<?php

namespace App\Actions;

use App\DataTransferObjects\RepositoryItemDTO;
use App\Entity\GithubRepository;
use App\Repository\GithubRepoRepository;

class GetGithubRepositoryAction
{
    public function __construct(
        private readonly GithubRepoRepository      $githubRepository,
    ) {}

    public function execute(int $repoId): ?RepositoryItemDTO
    {
        /** @var GithubRepository|null $repo */
        $repo = $this->githubRepository->find($repoId);
        if (!$repo) {
            return null;
        }
        return RepositoryItemDTO::fromObject($repo);
    }
}
