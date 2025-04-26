<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ArticleLike;

final class SpotifyController extends AbstractController
{
    private SpotifyService $spotifyService;
    private ArticleRepository $articleRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(SpotifyService $spotifyService, ArticleRepository $articleRepository, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->spotifyService = $spotifyService;
        $this->articleRepository = $articleRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/spotify/artist/{name}', name: 'app_spotify')]
    public function index(string $name): Response
    {
        $artistData = $this->spotifyService->getArtist($name);

        if (empty($artistData['artists']['items'])) {
            return $this->render('spotify/error.html.twig', [
                'error' => 'Artist not found.',
            ]);
        }

        $artist = $artistData['artists']['items'][0];

        $articles = $this->articleRepository->findBy(['artist' => $artist['name']]);

        return $this->render('spotify/artist.html.twig', [
            'name' => $artist['name'],
            'image' => $artist['images'][0]['url'] ?? null,
            'description' => $artist['genres'][0] ?? 'No description available',
            'followers' => $artist['followers']['total'],
            'articles' => $articles,
        ]);
    }

    #[Route('/spotify/artist/{artistName}/create-article', name: 'app_create_article', methods: ['POST'])]
    public function createArticle(Request $request, string $artistName): RedirectResponse
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        $title = $request->request->get('title');
        $content = $request->request->get('content');

        $slug = str_replace(' ', '-', $title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setArtist($artistName);
        $article->setUser($user);
        $article->setSlug($slug);
        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setUser($user);

        $this->entityManager->persist($like);
        $this->entityManager->persist($article);
        $this->entityManager->flush();


        $article->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_spotify', ['name' => $artistName]);
    }

    #[Route("/spotify/search", name:"search_artists")]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        if (!$query) {
            return new JsonResponse([]);
        }

        $artists = $this->spotifyService->getArtist($query);

        $results = array_map(function ($artist) {
            return [
                'name' => $artist['name'],
                'image' => $artist['images'][0]['url'] ?? '',
                'followers' => $artist['followers']['total'],
                'url' => $this->generateUrl('app_spotify', ['name' => $artist['name']]),
            ];
        }, $artists['artists']['items']);

        return new JsonResponse($results);
    }
}
