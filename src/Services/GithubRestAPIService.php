<?php

namespace App\Services;

use App\Config\GithubConfig;
use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Enums\GithubEndpointConfigEnum;
use App\Exceptions\GitHubConfigException;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class GithubRestAPIService implements GithubAPIServiceInterface
{
    public function __construct(
        private readonly GithubConfig $config,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'github.client')]
        public readonly HttpClientInterface $httpClient,
    ) {}

    /**
     * @throws Throwable
     */
    private function call(string $endpointName, string $method, array $params = [], array $query = []): array
    {
        $apiUrl =  '/' . ltrim($endpointName, '/');
        if ($params) {
            $apiUrl = vsprintf($apiUrl, $params);
        }
        try {
            return $this->httpClient
                ->request($method, $apiUrl, [
                    'query' => $query,
                    'headers' => $this->authHeader(),
                ])
                ->toArray();
        } catch (Throwable $e) {
            $this->logger->error('Http Error:' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'api_url' => $apiUrl,
                'method' => $method,
                'params' => $params,
                'query' => $query,
            ]);
            throw $e;
        }

    }

    /**
     * @param RepositorySearchParamsDTO $builder
     * @return Iterator
     */

    public function fetchAllRepositories(RepositorySearchParamsDTO $builder): Iterator
    {

        $endpoint = null;
        try {
            $endpoint = $this->resolveAPIEndpoint(GithubEndpointConfigEnum::FETCH_REPOSITORIES);
            $data = $this->call(
                endpointName: $endpoint['path'],
                method: $endpoint['method'],
                query: $builder->buildQuery(),
            );
            if ($data && isset($data['total_count']) && $data['total_count']) {
                foreach ($data['items'] as $item) {
                    yield RepositoryItemDTO::fromResponse($item);
                }
            }
        } catch (Throwable $e) {
            $this->logger->error('API Service Error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'query' => $builder->buildQuery(),

            ]);
        }

        return null;
    }

    public function fetchRepository(string $owner, string $repositoryName): ?RepositoryItemDTO
    {
        $endpoint = null;

        try {
            $endpoint = $this->resolveAPIEndpoint(GithubEndpointConfigEnum::GET_REPOSITORY);
            $data = $this->call(
                endpointName: $endpoint['path'],
                method: $endpoint['method'],
                params: [$owner, $repositoryName],
            );
            return RepositoryItemDTO::fromResponse($data);

        } catch (Throwable $e) {
            $this->logger->error('API Service Error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'repository' => $repositoryName,

            ]);
        }

        return null;
    }

    private function authHeader(): array
    {
        if (!empty($this->config->token)) {
            return [
                'Authorization' => "Bearer {$this->config->token}",
            ];
        }
        return [];
    }

    /**
     * @throws GitHubConfigException
     */
    private function resolveAPIEndpoint(GithubEndpointConfigEnum $endpointName): array
    {
        $endpoint = $this->config->endpoints[$endpointName->value] ?? null;
        if (!$endpoint
            || !isset($endpoint['path'])
            || !isset($endpoint['method'])) {
            throw new GitHubConfigException(
                param: $endpointName->value,
                message: 'endpoint not found or invalid',
            );
        }
        return $endpoint;
    }


}
