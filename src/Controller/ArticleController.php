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
use App\Entity\Comment;
use App\Form\CommentType;
use App\Message\GeneratePdfMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Article;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArticleController extends AbstractController
{
    private SpotifyService $spotifyService;
    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

    #[Route('/article/list', name: 'app_article_list')]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }
    
    #[Route('/article/{slug}', name: 'app_article')]
    public function show(string $slug, ArticleRepository $articleRepository, Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        $artistData = null;
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            return $this->redirectToRoute('app_article_list');
        }
    
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

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $comment->setArticle($article);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté !');

            return $this->redirectToRoute('app_article', ['slug' => $slug]);
        }
        $pdfFilename = sprintf('article_%d.pdf', $article->getId());
        $pdfPath     = $this->getParameter('kernel.project_dir') . '/public/pdfs/' . $pdfFilename;
        $pdfExists   = file_exists($pdfPath);
        $generationRequested = $session->get('pdf_generation_requested_' . $article->getId(), false);
         
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'artist' => $artistData,
            'form' => $form->createView(),
            'pdfExists' => $pdfExists,
            'pdfFilename' => $pdfFilename,
            'generationRequested' => $generationRequested,
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
            return $this->redirectToRoute('app_article_list');
        }

        if ($article->isLikedByUser($user)) {
            $this->addFlash('warning', 'Vous avez déjà liké cet article.');
            return $this->redirectToRoute('app_article', ['slug' => $slug]);
        }

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
            return $this->redirectToRoute('app_article_list');
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



    #[Route('/article/{slug}/export-pdf', name: 'app_article_export_pdf')]
    public function exportPdf(
        string $slug,
        ArticleRepository $articleRepository,
        MessageBusInterface $bus,
        Request $request,
        SessionInterface $session
    ): Response {
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        if (!$article) {
            return $this->redirectToRoute('app_article_list');
        }

        if (!$this->isCsrfTokenValid('generate-pdf' . $article->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        if (!$article || !$article->getUser()) {
            throw $this->createNotFoundException('L\'article ou l\'utilisateur associé n\'existe pas');
        }
        
        $bus->dispatch(new GeneratePdfMessage('article', (string) $article->getId()));

        $session->set('pdf_generation_requested_' . $article->getId(), true);

        $this->addFlash('info', 'La génération du PDF a démarré.');

        $this->addFlash('info', 'La génération du PDF a été mise en file d’attente. Vous pourrez le télécharger bientôt.');

        return $this->redirectToRoute('app_article', ['slug' => $slug]);
    }

    #[Route('/pdfs/article/{id}', name: 'app_pdf_download')]
    public function downloadPdf(int $id): Response
    {
        $filename = "article_{$id}.pdf";
        $path     = $this->getParameter('kernel.project_dir') . "/public/pdfs/{$filename}";

        if (!file_exists($path)) {
            throw $this->createNotFoundException('PDF non trouvé. Génération toujours en cours ?');
        }

        return $this->file($path, $filename);
    }

    #[Route('/api/articles', name: 'api_article_list', methods: ['GET'])]
    public function apiList(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();
        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'slug' => $article->getSlug(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/articles', name: 'api_article_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function apiCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setSlug($data['slug']);
        $user = $this->getUser();
        $article->setUser($user);

        $em->persist($article);
        $em->flush();

        return $this->json(['message' => 'Article créé', 'id' => $article->getId()], 201);
    }

    #[Route('/api/articles/{id}', name: 'api_article_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function apiUpdate(Request $request, Article $article, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }
        if (isset($data['slug'])) {
            $article->setSlug($data['slug']);
        }

        $em->flush();

        return $this->json(['message' => 'Article mis à jour']);
    }

    #[Route('/api/articles/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function apiDelete(Article $article, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($article->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet article.');
        }

        $em->remove($article);
        $em->flush();

        return $this->json(['message' => 'Article supprimé']);
    }

    #[Route('/api/articles/{id}', name: 'api_article_get_one', methods: ['GET'])]
    public function apiGet(Article $article): JsonResponse
    {
        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'slug' => $article->getSlug(),
        ]);
    }
}
