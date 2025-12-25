<?php

namespace App\Enums;

/**
 * API Search Sort By enum
 */
enum RepositorySortByEnum: string
{
    case STARS = 'stars';
    case FORKS = 'forks';
    case HELP_WANTED_ISSUES = 'help-wanted-issues';
    case UPDATED = 'updated';

}
