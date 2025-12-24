<?php

namespace App\Actions;

use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\GithubReposRefreshResponseDTO;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Enums\RepositorySortByEnum;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class RefreshGithubRepositoriesAction
{
    public function __construct(
        private readonly GithubAPIServiceInterface $githubAPIService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $pool,
    ) {}

    /**
     * @param int $per_page // max per page 100 & rate limit (10/60 or with auth 30/60)
     * @param int $max_result // max result is 1000
     * @return GithubReposRefreshResponseDTO
     */
    public function execute(int $per_page = 100, int $max_result = 500): GithubReposRefreshResponseDTO
    {
        $per_page = $per_page  <= 100 && $per_page > 0 ? $per_page : 100;
        $max_result = $max_result <= 1000 && $max_result > 0 ? $max_result : 500;
        $query = [
            'language' => 'PHP',
            'sort' => RepositorySortByEnum::STARS->value,
            'per_page' => $per_page,
            'page' => 1,
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
                if ($buffer) {
                    $this->storeGithubRepo($buffer);
                }
            }



            $this->pool->clear();

        } catch (\Throwable $e) {
            $this->logger->error("Refresh error: " . $e->getMessage(), [
                'trace' => $e->getTrace(),
            ]);
            $resp->message = "Error occurred while trying to start the refresh";
            $resp->ok = false;
        }

        return $resp;

    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    private function storeGithubRepo(array $rows): void
    {
        // unfortunately, symfony doctrine orm don't have upsert like laravel
        // this raw query, has low memory impact, good on performance
        $raw_query = <<<SQL
            INSERT INTO github_repositories (id, name, url, stars_count, description,last_pushed_at , created_at,updated_at)
            VALUES (:id, :name, :url, :stars_count,:description,:last_pushed_at, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
              name = VALUES(name),
              url = VALUES(url),
              stars_count = VALUES(stars_count),
              last_pushed_at = VALUES(last_pushed_at),
              description = VALUES(description),
              updated_at= VALUES(updated_at)
            SQL;
        $dbConn = $this->entityManager->getConnection();
        $dbConn->beginTransaction();
        try {
            $stmt = $dbConn->prepare($raw_query);
            foreach ($rows as $row) {
                foreach ($row as $column => $value) {
                    $stmt->bindValue($column, $value);
                }
                $stmt->executeStatement();
            }
            $dbConn->commit();
        } catch (\Throwable $e) {
            $dbConn->rollBack();
            throw $e;
        }


    }

}
