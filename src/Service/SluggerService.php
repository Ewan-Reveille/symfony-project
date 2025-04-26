<?php
namespace App\Service;

use App\Repository\ArticleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class SluggerService
{
    private SluggerInterface $slugger;
    private ArticleRepository $articleRepository;

    public function __construct(SluggerInterface $slugger, ArticleRepository $articleRepository)
    {
        $this->slugger = $slugger;
        $this->articleRepository = $articleRepository;
    }

    public function generateUniqueSlug(string $title): string
    {
        $slug = strtolower($this->slugger->slug($title));
        $originalSlug = $slug;
        $i = 1;

        while ($this->articleRepository->findOneBy(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
