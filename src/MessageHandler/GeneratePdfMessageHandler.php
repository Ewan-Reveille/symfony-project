<?php

namespace App\MessageHandler;

use App\Message\GeneratePdfMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\SpotifyService;

#[AsMessageHandler]
class GeneratePdfMessageHandler
{
    public function __construct(
        private Environment $twig,
        private ArticleRepository $articleRepo,
        private UserRepository $userRepo,
        private SpotifyService $spotifyService
    ) {}

    public function __invoke(GeneratePdfMessage $message)
    {
        $dompdf = new Dompdf(new Options(['isRemoteEnabled' => true]));

        $type = $message->getType();
        $payload = $message->getPayload();

        $html = match ($type) {
            'artist' => $this->renderArtist($payload),
            'article' => $this->renderArticle($payload),
            default => throw new \InvalidArgumentException("Unknown PDF type.")
        };

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save to disk
        $output = $dompdf->output();
        file_put_contents(__DIR__ . "/../../public/pdfs/{$type}_{$payload}.pdf", $output);
    }

    private function renderArtist(string|int $name): string
    {
        $artistData = $this->spotifyService->getArtist($name);
        $artist = $artistData['artists']['items'][0] ?? null;

        if (!$artist) {
            throw new \RuntimeException("Artist not found: $name");
        }

        return $this->twig->render('pdf/artist.html.twig', [
            'name' => $artist['name'],
            'image' => $artist['images'][0]['url'] ?? null,
            'description' => $artist['genres'][0] ?? 'No description available',
            'followers' => $artist['followers']['total'],
        ]);
    }


    private function renderArticle(string|int $payload): string
    {
        $id = (int) $payload;
        $article = $this->articleRepo->find($id);

        if (!$article) {
            throw new \RuntimeException("Article not found with ID $id.");
        }

        return $this->twig->render('pdf/article.html.twig', ['article' => $article]);
    }
}
