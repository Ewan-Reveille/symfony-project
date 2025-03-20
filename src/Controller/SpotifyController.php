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

final class SpotifyController extends AbstractController
{
    private SpotifyService $spotifyService;
    private ArticleRepository $articleRepository;
    private UserRepository $userRepository;

    public function __construct(SpotifyService $spotifyService, ArticleRepository $articleRepository, UserRepository $userRepository)
    {
        $this->spotifyService = $spotifyService;
        $this->articleRepository = $articleRepository;
        $this->userRepository = $userRepository;
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

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setArtist($artistName);
        $article->setUser($user);
        $article->setLikes(0);
        $article->setCreatedAt(new \DateTimeImmutable());

        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        return $this->redirectToRoute('app_spotify', ['name' => $artistName]);
    }

    #[Route("/spotify/search", name:"search_artists")]
    public function search(Request $request): JsonResponse
    {
        // Get the search query from the request
        $query = $request->query->get('query', '');

        // If no query is provided, return an empty result
        if (!$query) {
            return new JsonResponse([]);
        }

        // Fetch the artists from Spotify
        $artists = $this->spotifyService->getArtist($query);

        // Map the artist data to the format needed for the response
        $results = array_map(function ($artist) {
            return [
                'name' => $artist['name'],
                'image' => $artist['images'][0]['url'] ?? '',
                'followers' => $artist['followers']['total'],
                'url' => $this->generateUrl('app_spotify', ['name' => $artist['name']]),
            ];
        }, $artists['artists']['items']);

        // Return the artist search results as JSON
        return new JsonResponse($results);
    }
}
