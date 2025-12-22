<?php

namespace App\Services;

use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubRestAPIService implements GithubAPIServiceInterface
{
    private array $endpoints;
    public function __construct(
        #[Autowire('%github_api.base_url%')]
        private readonly string $apiBaseUrl,
        #[Autowire('%github_api.token%')]
        private readonly string $token,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'github.client')]
        private HttpClientInterface $httpClient,
    ) {
        $this->loadEndpoints();
    }

    /**
     * @throws \Throwable
     */
    private function call(string $endpointName, string $method, array $params = [], array $query = []): array
    {
        $apiUrl = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($endpointName, '/');
        if ($params) {
            $apiUrl = vsprintf($apiUrl, $params);
        }
        try {
            return $this->httpClient
                ->withOptions([
                    'headers' => $this->authHeader(),
                ])
                ->request($method, $apiUrl, $query)
                ->toArray(true);
        } catch (\Throwable $e) {
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

        return null;
    }

    public function fetchRepository(string $owner, string $repositoryName): ?RepositoryItemDTO
    {
        $endpoint = null;
        try {
            $endpoint = $this->endpoints['repo_get'] ?? null;
            if (!$endpoint || !isset($endpoint['path'])) {
                throw new \RuntimeException('Endpoint not defines');
            }
            $data = $this->call(
                endpointName: $endpoint['path'],
                method: $endpoint['method'],
                params: [$owner, $repositoryName],
            );
            return RepositoryItemDTO::fromResponse($data);

        } catch (\Throwable $e) {
            $this->logger->error('Http Error:' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'repository' => $repositoryName,

            ]);
        }

        return null;
    }

    private function authHeader(): array
    {
        if (!empty($this->token)) {
            return [
                'Authorization' => "Bearer {$this->token}",
            ];
        }
        return [];
    }

    private function loadEndpoints(): void
    {
        $endpoints = $this->parameterBag->get('github_api.endpoints') ?? [];
        foreach ($endpoints as $endpointName => $endpoint) {
            $this->endpoints[$endpointName] = $endpoint;
        }
    }
}
