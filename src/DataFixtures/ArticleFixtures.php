<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $article = new Article();
            $article->setTitle("Article $i");
            $article->setContent("Contenu d'article $i");
            $manager->persist($article);
        }

        $manager->flush();
    }
}
