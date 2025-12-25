<?php

namespace App\Actions;

use App\Config\GithubAPIConfig;
use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\GithubReposRefreshResponseDTO;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Enums\RepositorySortByEnum;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Refresh github repositories action will fetch results
 */
readonly class RefreshGithubRepositoriesAction
{
    // represent one of challenge requirements, PHP projects only
    private const DEFAULT_SEARCH_LANGUAGE = 'PHP';
    // represent one of the challenge requirements, sort by stars count descending
    private const DEFAULT_SORT_BY = RepositorySortByEnum::STARS->value;
    private int $maxPerPage;
    private int $maxResultCount;
    public function __construct(
        private GithubAPIServiceInterface $githubAPIService, // GitHub API Service
        private EntityManagerInterface    $entityManager, // DB entity manager
        private LoggerInterface           $logger,// Logger Interface
        GithubAPIConfig                   $apiConfig // Github API Config
    ) {
        $this->maxPerPage = $apiConfig->apiMaxPerPageCount;
        $this->maxResultCount = $apiConfig->apiMaxResultCount;
    }

    /**
     * @param int $per_page // max per page 100 & rate limit (10/60 or with auth 30/60)
     * @param int $max_result // max result is 1000
     * @return GithubReposRefreshResponseDTO
     */
    public function execute(
        int $per_page = 100,
        int $max_result = 500
    ): GithubReposRefreshResponseDTO {
        // ensuring that we don't get input out of range
        // that insures valid handling of the api
        $per_page = min($this->maxPerPage, max(1, $per_page));
        $max_result = min($this->maxResultCount, max(1, $max_result));

        $this->logger->info("Starting refresh github repos action", [
            'language' => self::DEFAULT_SEARCH_LANGUAGE,
            'per_page' => $per_page,
            'max_result' => $max_result,
            'sort_by' => self::DEFAULT_SORT_BY,
        ]);

        $query = [
            'language' => self::DEFAULT_SEARCH_LANGUAGE,
            'sort' => self::DEFAULT_SORT_BY,
            'per_page' => $per_page,
            'page' => 1, // starting from first page by default
        ];
        $pages = ceil($max_result / $per_page);
        $resp = new GithubReposRefreshResponseDTO(
            message: "Github repositories refresh operation successfully finished",
        );

        try {
            $builder = RepositorySearchParamsDTO::fromArray($query) ;
            for ($i = 1; $i <= $pages; $i++) {
                $builder->page = $i;
                $buffer = [];
                /** @var RepositoryItemDTO $githubRepository */
                foreach ($this->githubAPIService->fetchAllRepositories($builder) as $githubRepository) {
                    $buffer[] = $githubRepository->asTableRow();
                }
                $this->logger->info("Refresh in progress: page {$i}", [
                    'buffer_count' => count($buffer),
                ]);
                if ($buffer) {
                    $this->logger->info("Saving content of page {$i}");
                    $this->createOrUpdateRepositories($buffer);
                    $this->logger->info("Successfully done with page {$i}");
                } else {
                    $this->logger->warning("No records saved for page {$i}");
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error("Refresh error: " . $e->getMessage(), [
                'trace' => $e->getTrace(),
                'code' => $e->getCode(),
            ]);
            $resp->message = "Error occurred while trying to start the refresh";
            $resp->ok = false;
        }

        return $resp;

    }

    /**
     * create or update repositories by chunk
     * @throws \Throwable
     * @throws Exception
     */
    private function createOrUpdateRepositories(array $rows, int $chunksize = 50): void
    {
        // unfortunately, symfony doctrine orm don't have upsert like laravel
        // this raw query, has low memory impact, good on performance
        $raw_query = <<<SQL
            INSERT INTO github_repositories (id, name, url, stars_count, description,last_pushed_at , created_at,updated_at)
            VALUES %s
            ON DUPLICATE KEY UPDATE
              name = VALUES(name),
              url = VALUES(url),
              stars_count = VALUES(stars_count),
              last_pushed_at = VALUES(last_pushed_at),
              description = VALUES(description),
              updated_at= VALUES(updated_at)
            SQL;
        $dbConn = $this->entityManager->getConnection();
        // all chunk get saved or nothing
        $dbConn->beginTransaction();
        try {
            foreach (array_chunk($rows, $chunksize) as $chunk) {
                $values = [];
                $params = [];
                foreach ($chunk as $i => $row) {
                    $columns = array_map(fn($r) => '?', $row);
                    $params[] = $row['id'];
                    $params[] = $row['name'];
                    $params[] = $row['url'];
                    $params[] = $row['stars_count'];
                    $params[] = $row['description'];
                    $params[] = $row['last_pushed_at'];
                    $params[] = $row['created_at'];
                    $params[] = $row['updated_at'];
                    $values[] = '(' . implode(',', $columns) . ')';
                }
                $upsertSql = sprintf($raw_query, implode(', ', $values));
                $this->logger->debug('Executing statement', [
                    'query' => $upsertSql,
                    'params' => $params,
                    'per' => $chunksize,
                ]);
                $affected = $dbConn->executeStatement($upsertSql, $params);
                $this->logger->debug('Affected rows', [
                    'affected' => $affected,
                ]);
            }
            $dbConn->commit();
        } catch (\Throwable $e) {
            $dbConn->rollBack();
            throw $e;
        }


    }

}
