<?php

namespace App\Services;

use App\Config\GithubConfig;
use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Exceptions\GitHubConfigException;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class GithubRestAPIService implements GithubAPIServiceInterface
{
    public function __construct(
        private GithubConfig $config,
        private LoggerInterface $logger,
        #[Autowire(service: 'github.client')]
        public HttpClientInterface $httpClient,
    ) {}

    /**
     * @throws Throwable
     */
    private function call(string $urlOrPath, string $method = 'GET', array $query = []): array
    {


        try {
            return $this->httpClient
                ->request($method, $urlOrPath, [
                    'query' => $query,
                    'headers' => $this->authHeader(),
                ])
                ->toArray();
        } catch (Throwable $e) {
            $this->logger->error('Http Error:' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'api_url' => $urlOrPath,
                'query' => $query,
                'auth_headers' => $this->authHeader(),
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

        try {
            $endpoint = $this->config->searchEndpoint;
            if (!$endpoint) {
                throw new GitHubConfigException(
                    param: 'searchEndpoint',
                    message: 'search endpoint config not found or invalid',
                );
            }
            $endpoint = '/' . ltrim($endpoint, '/');
            $data = $this->call(
                urlOrPath: $endpoint,
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


    private function authHeader(): array
    {
        if (!empty($this->config->token)) {
            return [
                'Authorization' => "Bearer {$this->config->token}",
            ];
        }
        return [];
    }




}
