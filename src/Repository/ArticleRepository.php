<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findMostLiked(int $limit = 5)
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
