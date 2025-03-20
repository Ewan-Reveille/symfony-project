<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $mostLikedArticles = $this->articleRepository->findMostLiked();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'mostLikedArticles' => $mostLikedArticles,
        ]);
    }
}
