<?php

namespace App\Actions;

use App\DataTransferObjects\RepositoryItemDTO;
use App\Repository\GithubRepoRepository;
use Doctrine\Common\Collections\Criteria;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

class PaginateGithubRepositoriesAction
{
    public function __construct(
        private readonly GithubRepoRepository $repository,
        private readonly CacheItemPoolInterface  $cache
    ) {}
    private const ALLOWED_PER_PAGE = [10, 30, 60, 100];
    private const DEFAULT_PER_PAGE = 10;

    public function execute(Request $request): array
    {
        $search = $request->query->get('search', null);
        $minStars = max(0, (int) $request->query->get('min_stars', 0));

        // Validate and enforce per_page values
        $requestedPerPage = (int) $request->query->get('per_page', self::DEFAULT_PER_PAGE);
        $per_page = in_array($requestedPerPage, self::ALLOWED_PER_PAGE)
            ? $requestedPerPage
            : self::DEFAULT_PER_PAGE;

        $page = max(1, (int) $request->query->get('page', 1));

        $cacheFilters = [
            'search' => $search,
            'min_stars' => $minStars,
        ];
        $cache_key = 'repos_filter_' . hash('sha256', json_encode($cacheFilters));


        $cachedResult = $this->cache->get($cache_key, function (ItemInterface $item) use ($cacheFilters) {
            $item->expiresAfter(3600);
            return $this->buildFilteredResults($cacheFilters);
        });

        // Paginate from cached results
        $offset = ($page - 1) * $per_page;
        $paginatedIds = array_slice($cachedResult['ids'], $offset, $per_page);

        // Fetch only the repos for current page
        $repos = !empty($paginatedIds)
            ? $this->repository->findReposByIds($paginatedIds)
            : [];

        $mappedRepositories = array_map(fn($repo) => RepositoryItemDTO::fromObject($repo), $repos);

        return [
            'repositories' => $mappedRepositories,
            'page' => $page,
            'pageCount' => count($paginatedIds),
            'totalAll' => $cachedResult['totalCount'],
        ];
    }

    private function buildFilteredResults(array $filter = []): array
    {
        $search = $filter['search'] ?? null;
        $min_stars = $filter['min_stars'] ?? 0;
        $criteria = new Criteria();
        if (!empty($search)) {
            $criteria->where(Criteria::expr()->contains('name', $search));
        }
        if ($min_stars > 0) {
            $criteria->andWhere(Criteria::expr()->gte('stars_count', $min_stars));
        }

        $criteria->orderBy(['stars_count' => 'DESC']);
        $repos = $this->repository->matching($criteria);

        return [
            'ids' => $repos->map(fn($repo) => $repo->getId())->toArray(),
            'totalCount' => $repos->count(),
        ];
    }
}
