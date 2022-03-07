<?php

namespace App\Repository;

use App\Entity\DistributorProducts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DistributorProducts|null find($id, $lockMode = null, $lockVersion = null)
 * @method DistributorProducts|null findOneBy(array $criteria, array $orderBy = null)
 * @method DistributorProducts[]    findAll()
 * @method DistributorProducts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DistributorProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DistributorProducts::class);
    }

    public function getProductStockCount($product_id): array
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('sum(d.stockCount)')
            ->andWhere('d.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->groupBy('d.product')
            ->setMaxResults(1);
        return $queryBuilder->getQuery()->getResult();
    }

    public function getLowestPrice($product_id): array
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('d.unitPrice')
            ->andWhere('d.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->orderBy('d.unitPrice', 'ASC')
            ->setMaxResults(1);
        return $queryBuilder->getQuery()->getResult();
    }
}
