<?php
namespace App\Controller;

use App\Message\GeneratePdfMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ExportController extends AbstractController
{
    #[Route('/export/artist/{name}', name: 'export_artist_pdf')]
    public function exportArtist(string $name, MessageBusInterface $bus): Response
    {
        $bus->dispatch(new GeneratePdfMessage('artist', $name));

        $filepath = $this->getParameter('kernel.project_dir') . '/public/pdfs/artist_' . $name . '.pdf';

        if (!file_exists($filepath)) {
            throw $this->createNotFoundException("The PDF file does not exist.");
        }

        return new StreamedResponse(function() use ($filepath) {
            readfile($filepath);
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="artist_' . $name . '.pdf"',
        ]);
    }

    #[Route('/export/article/{id}', name: 'export_article_pdf')]
    public function exportArticle(int $id, MessageBusInterface $bus): Response
    {
        $bus->dispatch(new GeneratePdfMessage('article', $id));

        $filepath = $this->getParameter('kernel.project_dir') . '/public/pdfs/article_' . $id . '.pdf';

        if (!file_exists($filepath)) {
            throw $this->createNotFoundException("The PDF file does not exist.");
        }

        return new StreamedResponse(function() use ($filepath) {
            readfile($filepath);
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="article_' . $id . '.pdf"',
        ]);
    }
}
