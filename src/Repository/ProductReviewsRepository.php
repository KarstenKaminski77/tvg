<?php

namespace App\Repository;

use App\Entity\ProductReviews;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductReviews|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductReviews|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductReviews[]    findAll()
 * @method ProductReviews[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductReviewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReviews::class);
    }

     /**
      * @return ProductReviews[] Returns an array of ProductReviews objects
      */
    public function getAverageRating($product_id)
    {
        return $this->createQueryBuilder('p')
            ->select('AVG(p.rating), COUNT(p.id)')
            ->andWhere('p.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
}
