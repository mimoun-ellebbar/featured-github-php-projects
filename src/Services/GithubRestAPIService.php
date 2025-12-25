<?php

namespace App\Services;

use App\Config\GithubAPIConfig;
use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Exceptions\GithubAPIGeneralException;
use App\Exceptions\GitHubConfigException;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * GitHub REST Api Service
 * use HttpClientInterface client for http requests
 * and uses LoggerInterface for debug traceability
 * injected GitHub api global config class for easy integration
 */
readonly class GithubRestAPIService implements GithubAPIServiceInterface
{
    public function __construct(
        private GithubAPIConfig    $config,
        private LoggerInterface    $logger,
        #[Autowire(service: 'github.client')]
        public HttpClientInterface $httpClient,
    ) {}

    /**
     * Handling http request for the endpoint/url & method
     * then return the content as array or fail with exception
     * @throws Throwable
     */
    private function call(string $urlOrPath, string $method = 'GET', array $query = []): array
    {
        try {
            $resp =  $this->httpClient
                ->request('GET', $urlOrPath, [
                    'query' => $query,
                    'headers' => $this->authHeader(),
                ]);
            if ($resp->getStatusCode() !== 200) {
                $errorMessage = match ($resp->getStatusCode()) {
                    Response::HTTP_UNPROCESSABLE_ENTITY => "Validation failed, or the endpoint has been spammed.",
                    Response::HTTP_SERVICE_UNAVAILABLE => "Service unavailable",
                    default => "Unknown HTTP Error"
                };
                throw new GithubAPIGeneralException($errorMessage, code: $resp->getStatusCode());
            }
            return $resp->toArray();

        } catch (Throwable $e) {
            $this->logger->error('Http Error:' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'api_url' => $urlOrPath,
                'query' => $query,
                'code' => $e->getCode(),
                'auth_headers' => $this->authHeader(),
            ]);
            throw $e;
        }

    }

    /**
     * fetch all repositories matching the given criteria and return a generator
     * so results are streamed instead of loaded all at once.
     * This is ideal for memory efficiency and lets us process large result sets safely (batch-by-batch)
     * without exhausting RAM.
     * @param RepositorySearchParamsDTO $builder
     * @return Iterator
     * @throws GithubAPIGeneralException|GitHubConfigException
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
        } catch (GitHubConfigException $e) {
            $this->logger->error('GitHub REST API Config-> Error : ' . $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('GitHub REST API->Service Error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'query' => $builder->buildQuery(),

            ]);
            if ($e instanceof GithubAPIGeneralException) {
                throw $e;
            }
            throw new GithubAPIGeneralException(
                message: "Github REST API->Unknown Error Occurred: " . $e->getMessage(),
            );
        }

        return null;
    }


    /**
     * Authenticate GitHub account with the api call if any
     * then inject into the header
     * @return array|string[]
     */
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
