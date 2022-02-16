<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function getArticalCountOfCategory()
    {
        $queryBuilder = $this->createQueryBuilder('c')
                ->select('c.categoryName, c.id, COUNT(b.id) as blog_count')
                ->Join('c.blog', 'b')
                ->groupBy('c.categoryName')
                ->addOrderBy('c.id', 'ASC');
                /*->setParameter("storeId", $storeId);*/
                /*$queryBuilder->addOrderBy('vi.displayorder', 'desc');*/
        return $queryBuilder->getQuery()->getResult();
    }

}