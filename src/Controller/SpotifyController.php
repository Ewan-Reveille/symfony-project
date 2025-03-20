<?php

namespace App\Controller;

use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SpotifyController extends AbstractController
{
    private SpotifyService $spotifyService;

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
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

        return $this->render('spotify/artist.html.twig', [
            'name' => $artist['name'],
            'image' => $artist['images'][0]['url'] ?? null,
            'description' => $artist['genres'][0] ?? 'No description available',
            'followers' => $artist['followers']['total'],
        ]);
    }
}
