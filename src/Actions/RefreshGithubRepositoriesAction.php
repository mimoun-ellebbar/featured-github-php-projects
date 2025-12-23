<?php

namespace App\Actions;

use App\Enums\RepositorySortByEnum;
use App\Message\PullRepositoriesMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RefreshGithubRepositoriesAction
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @param int $per_page // max per page 100 & rate limit (10/60 or with auth 30/60)
     * @param int $max_result // max result is 1000
     * @return void
     * @throws ExceptionInterface
     */
    public function execute(int $per_page = 100, int $max_result = 500): void
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

        for ($i = 0; $i < $pages; $i++) {
            $query['page'] = $i;
            $this->messageBus->dispatch(
                new PullRepositoriesMessage($query)
            );
        }
    }

}
