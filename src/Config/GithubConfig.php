<?php

namespace App\Config;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Centralized configuration object for GitHub API Service integration.
 *
 * Groups all GitHub-related settings into a single immutable value object
 * to avoid fat constructors and improve maintainability (who would like fat constructors right ?).
 *
 * Injected via dependency injection and easily extensible as the
 * integration grows.
 */
final class GithubConfig
{
    public function __construct(
        public readonly string $token,
        public string $searchEndpoint,
    ) {}
}
