<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SpotifyService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\ArticleLike;
use App\Entity\User;

class ArticleController extends AbstractController
{
    private SpotifyService $spotifyService;
    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

    #[Route('/article/{slug}', name: 'app_article')]
    public function show(string $slug, ArticleRepository $articleRepository): Response
    {
        $artistData = null;
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if ($article->getArtist()) {
            $spotifyArtist = $this->spotifyService->getArtist($article->getArtist());

            if (!empty($spotifyArtist['artists']['items'])) {
                $artist = $spotifyArtist['artists']['items'][0];

                $artistData = [
                    'name' => $artist['name'],
                    'image' => $artist['images'][0]['url'] ?? null,
                    'description' => $artist['genres'][0] ?? 'No description available',
                    'followers' => $artist['followers']['total'],
                ];
            }
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'artist' => $artistData,
        ]);
    }

    #[Route('/article/{slug}/like', name: 'app_article_like')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(
        string $slug,
        ArticleRepository $articleRepository,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $article = $articleRepository->findOneBy(['slug' => $slug]);
        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        // 1. Vérifie si déjà liké
        if ($article->isLikedByUser($user)) {
            $this->addFlash('warning', 'Vous avez déjà liké cet article.');
            return $this->redirectToRoute('app_article', ['slug' => $slug]);
        }

        // 2. Crée un ArticleLike et le lie à l'article + à l'utilisateur
        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setUser($user);

        $em->persist($like);
        $em->flush();

        $this->addFlash('success', 'Merci pour votre like !');

        return $this->redirectToRoute('app_article', ['slug' => $slug]);
    }



    #[Route('/article/{slug}/edit', name: 'app_article_edit')]
    public function edit(
        string $slug,
        Request $request,
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article', ['slug' => $article->getSlug()]);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/{slug}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(
        string $slug,
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        if (!$article) {
            return $this->redirectToRoute('app_article_list');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        return $this->redirectToRoute('app_article_list');
    }

    #[Route('/article/no-article', name: 'app_article_absent')]
    public function absent(): Response
    {
        return $this->render('article/noarticle.html.twig');
    }

    #[Route('/article/list', name: 'app_article_list')]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        if (!$articles) {
            return $this->redirectToRoute('app_article_absent');
        }

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/{slug}/export-pdf', name: 'app_article_export_pdf')]
    public function exportPdf(
        string $slug,
        ArticleRepository $articleRepository,
        SpotifyService $spotifyService
    ): Response {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $artistData = null;
        if ($article->getArtist()) {
            $spotifyArtist = $spotifyService->getArtist($article->getArtist());

            if (!empty($spotifyArtist['artists']['items'])) {
                $artist = $spotifyArtist['artists']['items'][0];

                $artistData = [
                    'name' => $artist['name'],
                    'image' => $artist['images'][0]['url'] ?? null,
                    'description' => $artist['genres'][0] ?? 'No description available',
                    'followers' => $artist['followers']['total'],
                ];
            }
        }

        // Générer le HTML
        $html = $this->renderView('pdf/article.html.twig', [
            'article' => $article,
            'artist' => $artistData,
        ]);

        // Transformer le HTML en PDF
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        // Retourner le PDF en téléchargement direct
        return new Response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="article.pdf"',
            ]
        );
    }

}
