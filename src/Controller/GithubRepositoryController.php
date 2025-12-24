<?php

namespace App\Controller;

use App\Actions\PaginateGithubRepositoriesAction;
use App\Actions\RefreshGithubRepositoriesAction;
use App\DataTransferObjects\RepositoryItemDTO;
use App\Entity\GithubRepository;
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

    #[Route('/github/repository/{id}', name: 'app_github_repository_show')]
    public function show(?GithubRepository $repository): JsonResponse
    {
        $repoDto = $repository ? RepositoryItemDTO::fromObject($repository) : null;
        return $this->json(
            data: $repoDto?->toArray(true),
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
