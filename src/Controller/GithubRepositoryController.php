<?php

namespace App\Controller;

use App\Actions\GetGithubRepositoryAction;
use App\Actions\PaginateGithubRepositoriesAction;
use App\Actions\RefreshGithubRepositoriesAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GithubRepositoryController extends AbstractController
{
    #[Route('/github/repository/', name: 'app_github_repository')]
    public function index(Request $request, PaginateGithubRepositoriesAction $action): Response
    {
        $data = $action->execute($request);
        return $this->render('github_repository/index.html.twig', $data);
    }

    #[Route('/github/repository/{repo_id}', name: 'app_github_repository_show')]
    public function show(int $repo_id, GetGithubRepositoryAction $action): JsonResponse
    {
        $repoDto = $action->execute($repo_id);
        return $this->json(
            data: $repoDto?->toArray(),
            status: $repoDto ? Response::HTTP_OK : Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/github/refresh', name: 'app_github_repository_refresh', methods: ['POST'])]
    public function refresh(RefreshGithubRepositoriesAction $action): JsonResponse
    {
        $response = $action->execute();
        return $this->json(
            data: $response->toArray(),
            status: $response->ok ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
