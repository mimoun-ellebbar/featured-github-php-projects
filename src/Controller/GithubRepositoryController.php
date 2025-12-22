<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GithubRepositoryController extends AbstractController
{
    #[Route('/github/repository', name: 'app_github_repository')]
    public function index(): Response
    {
        return $this->render('github_repository/index.html.twig', [
            'controller_name' => 'GithubRepositoryController',
        ]);
    }
}
