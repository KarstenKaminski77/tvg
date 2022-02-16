<?php

namespace App\Repository;

use App\Entity\Baskets;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Baskets|null find($id, $lockMode = null, $lockVersion = null)
 * @method Baskets|null findOneBy(array $criteria, array $orderBy = null)
 * @method Baskets[]    findAll()
 * @method Baskets[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BasketsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Baskets::class);
    }

    // /**
    //  * @return Baskets[] Returns an array of Baskets objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Baskets
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
