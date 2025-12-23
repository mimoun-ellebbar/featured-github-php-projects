<?php

namespace App\Contracts;

use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;

interface GithubAPIServiceInterface
{
    public function fetchAllRepositories(RepositorySearchParamsDTO $builder): \Iterator;
}
