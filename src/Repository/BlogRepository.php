<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    /**
     * @return Branch[]
     */
    public function getFindBlogs(): object
    {
        $queryBuilder = $this->createQueryBuilder('b')
        ->orderBy('b.displayDate', 'DESC');
        return $queryBuilder->getQuery();
    }
    public function getIsPromotionBlogs(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            "SELECT b FROM App\Entity\Blog b WHERE b.isPromotion = '1'"
        )->useQueryCache(true)->enableResultCache(86400);
        return $query->getResult();
    }

    public function getCategoryBlogs($cat_id): object
    {
        $queryBuilder = $this->createQueryBuilder('b')
        ->select('b,c')
                ->Join('b.blogCategory', 'c')
                ->where('c.id = :cat_id')
                ->setParameter('cat_id', $cat_id);
        return $queryBuilder->getQuery();
    }

    public function getSearchBlogs($keywords): object
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->select('b')
            ->where('MATCH_AGAINST(b.title) AGAINST(:keyword boolean)>0')
            ->setParameter('keyword', $keywords.'*');
        return $queryBuilder->getQuery();
    }
}