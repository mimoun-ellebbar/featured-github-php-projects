<?php

namespace App\Enums;

enum RepositorySortByEnum: string
{
    case STARS = 'stars';
    case FORKS = 'forks';
    case HELP_WANTED_ISSUES = 'help-wanted-issues';
    case UPDATED = 'updated';

}
