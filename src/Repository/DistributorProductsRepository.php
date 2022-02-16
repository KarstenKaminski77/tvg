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

    // /**
    //  * @return DistributorProducts[] Returns an array of DistributorProducts objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DistributorProducts
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
