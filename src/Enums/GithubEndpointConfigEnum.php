<?php

namespace App\Enums;

enum GithubEndpointConfigEnum: string
{
    case GET_REPOSITORY = 'repo_get';
    case FETCH_REPOSITORIES = 'search_repos';
}
