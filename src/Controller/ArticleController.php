<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class ArticleController extends AbstractController
{
    #[Route('/article/{slug}', name: 'app_article')]
    public function show(string $slug, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article && $slug !== 'no-article') {
            return $this->redirectToRoute('app_article_absent');
        }

        if ($slug === 'no-article') {
            return $this->render('article/noarticle.html.twig');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/article/{slug}/like', name: 'app_article_like')]
    public function like(string $slug, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article ) {
            throw $this->createNotFoundException('Article not found');
        }

        $article->setLikes($article->getLikes() + 1);
        $articleRepository->save($article, true);

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
}
