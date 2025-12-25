<?php

namespace App\Actions;

use App\DataTransferObjects\RepositoryItemDTO;
use App\Repository\GithubRepoRepository;
use Doctrine\Common\Collections\Criteria;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class PaginateGithubRepositoriesAction
{
    public function __construct(
        private GithubRepoRepository $repository,
    ) {}
    // Fixed pages allowed to filter with
    private const ALLOWED_PER_PAGE = [10, 30, 60, 100];

    // default per page as fallback
    private const DEFAULT_PER_PAGE = 10;

    /**
     * this action taction the controller execute request
     * then parse query required params to filter and paginate
     * then eventually return back array with list of repos with some metadata
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        // search by name lookup
        $search = $request->query->get('search', null);
        // min stars filter, e.g min 20000, filter only repos above that number
        $minStars = max(0, (int) $request->query->get('min_stars', 0));
        // validate and enforce per_page values
        $requestedPerPage = (int) $request->query->get('per_page', self::DEFAULT_PER_PAGE);
        $per_page = in_array($requestedPerPage, self::ALLOWED_PER_PAGE)
            ? $requestedPerPage
            : self::DEFAULT_PER_PAGE;
        // ensure that the page not under 1
        $page = max(1, (int) $request->query->get('page', 1));

        $inputFilter = [
            'search' => $search,
            'min_stars' => $minStars,
        ];

        // building data based on requested filters
        $results = $this->buildFilteredResults(
            page: $page,
            per_page: $per_page,
            filter: $inputFilter
        );

        // Fetch only the repos for current page
        $repos = $results['repos'] ?? [];
        return [
            'repositories' => $repos, // repositories
            'page' => $page, // page filter from the request
            'pageCount' => $per_page, // per page from the request
            'totalAll' => $results['totalCount'] ?? 0, // all repos total info
        ];
    }

    /**
     * building the criteria to match the filters and pagination ranges
     * @param int $page
     * @param int $per_page
     * @param array $filter
     * @return array
     */
    private function buildFilteredResults(int $page, int $per_page, array $filter = []): array
    {
        // Paginate from cached results
        $offset = ($page - 1) * $per_page;
        $search = $filter['search'] ?? null;
        $min_stars = $filter['min_stars'] ?? 0;
        $criteria = new Criteria();
        if (!empty($search)) {
            $criteria->where(Criteria::expr()->contains('name', $search));
        }
        if ($min_stars > 0) {
            $criteria->andWhere(Criteria::expr()->gte('stars_count', $min_stars));
        }
        // always sorting with stars count to meet the coding challenge scope
        $criteria->orderBy(['stars_count' => 'DESC']);
        // adding pagination info
        $criteria->setFirstResult($offset)
        ->setMaxResults($per_page);
        $repos = $this->repository->matching($criteria);

        return [
            // Mapping Repo entity to the dto to control shown data
            'repos' => $repos->map(fn($repo) => RepositoryItemDTO::fromObject($repo))->toArray(),
            'totalCount' => $this->repository->count(),
        ];
    }
}
