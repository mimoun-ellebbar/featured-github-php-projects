<?php

namespace App\MessageHandler;

use App\Contracts\GithubAPIServiceInterface;
use App\DataTransferObjects\RepositoryItemDTO;
use App\DataTransferObjects\RepositorySearchParamsDTO;
use App\Message\PullRepositoriesMessage;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PullRepositoriesMessageHandler
{
    public function __construct(
        private GithubAPIServiceInterface $client,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}
    public function __invoke(PullRepositoriesMessage $message): void
    {
        try {
            $query = RepositorySearchParamsDTO::fromArray($message->query);
            $buffer = [];
            /** @var RepositoryItemDTO $githubRepository */
            foreach ($this->client->fetchAllRepositories($query) as $githubRepository) {
                $buffer[] = $githubRepository->asTableRow();
                if (count($buffer) == 100) {
                    $this->storeGithubRepo($buffer);
                    $buffer = [];
                }
            }
            if ($buffer) {
                $this->storeGithubRepo($buffer);
            }

        } catch (\Exception $exception) {
            $this->logger->error("Unexpected exception occurred", [
                'message' => $exception->getMessage(),
                'query' => $message->query,
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * @throws Exception|\Throwable
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
