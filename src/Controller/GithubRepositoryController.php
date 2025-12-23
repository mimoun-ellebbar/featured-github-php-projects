<?php

namespace App\Controller;

use App\Actions\GetGithubRepositoryAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GithubRepositoryController extends AbstractController
{
    #[Route('/github/repository/', name: 'app_github_repository')]
    public function index(): Response
    {
        return $this->render('github_repository/index.html.twig', [
            'controller_name' => 'GithubRepositoryController',
        ]);
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
}
